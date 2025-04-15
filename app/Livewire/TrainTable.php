<?php

namespace App\Livewire;

use App\Models\GtfsTrip;
use App\Models\Setting;
use App\Models\SelectedRoute;
use Livewire\Component;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class TrainTable extends Component
{
    public $trains = [];
    public $selectedRoutes = [];
    public $date;
    public $time;
    public $page = 1;
    public $perPage = 8;
    public $total = 0;

    protected $listeners = [
        'echo:train-statuses,TrainStatusUpdated' => 'handleTrainStatusUpdated'
    ];

    public function handleTrainStatusUpdated($event)
    {
        $this->loadTrains();
    }

    public function mount()
    {
        $this->date = Carbon::now()->format('Y-m-d');
        $this->time = Carbon::now()->format('H:i');
        $this->loadSelectedRoutes();
        $this->loadTrains();
    }

    public function loadSelectedRoutes()
    {
        $this->selectedRoutes = Cache::remember('selected_routes', 60, function () {
            return SelectedRoute::pluck('route_id')->toArray();
        });
    }

    public function loadTrains()
    {
        $query = $this->getTrainsQuery();
        $this->total = $query->count();
        $this->trains = $query->skip(($this->page - 1) * $this->perPage)
            ->take($this->perPage)
            ->get();
    }

    public function getTrainsQuery()
    {
        $today = Carbon::now()->format('Y-m-d');
        $currentTime = Carbon::now()->format('H:i:s');

        return GtfsTrip::query()
            ->select([
                'gtfs_trips.trip_short_name as number',
                'gtfs_stop_times.departure_time as departure',
                'gtfs_routes.route_long_name',
                'gtfs_routes.route_short_name',
                'train_statuses.status'
            ])
            ->join('gtfs_stop_times', function ($join) {
                $join->on('gtfs_trips.trip_id', '=', 'gtfs_stop_times.trip_id')
                    ->where('gtfs_stop_times.stop_sequence', 1);
            })
            ->join('gtfs_calendar_dates', function ($join) use ($today) {
                $join->on('gtfs_trips.service_id', '=', 'gtfs_calendar_dates.service_id')
                    ->whereDate('gtfs_calendar_dates.date', $today)
                    ->where('gtfs_calendar_dates.exception_type', 1);
            })
            ->join('gtfs_routes', 'gtfs_trips.route_id', '=', 'gtfs_routes.route_id')
            ->leftJoin('train_statuses', 'gtfs_trips.trip_id', '=', 'train_statuses.trip_id')
            ->whereIn('gtfs_trips.route_id', $this->selectedRoutes)
            ->where('gtfs_stop_times.departure_time', '>=', $currentTime)
            ->orderBy('gtfs_stop_times.departure_time');
    }

    public function nextPage()
    {
        if ($this->page * $this->perPage < $this->total) {
            $this->page++;
            $this->loadTrains();
        }
    }

    public function previousPage()
    {
        if ($this->page > 1) {
            $this->page--;
            $this->loadTrains();
        }
    }

    public function render()
    {
        return view('livewire.train-table', [
            'trains' => $this->trains,
            'total' => $this->total,
            'page' => $this->page,
            'perPage' => $this->perPage
        ]);
    }
} 