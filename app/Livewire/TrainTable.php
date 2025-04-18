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
            ->get()
            ->map(function ($train) {
                return [
                    'number' => $train->number,
                    'departure' => $train->departure,
                    'route_long_name' => $train->route_long_name,
                    'status' => $train->status_text ?? $train->train_status ?? 'on-time',
                    'status_color' => $train->color_rgb ?? '156,163,175',
                    'departure_platform' => $train->departure_platform ?? 'TBD',
                    'arrival_platform' => $train->arrival_platform ?? 'TBD'
                ];
            });

        // Debug the first train's data
        if ($this->trains->isNotEmpty()) {
            $firstTrain = $this->trains->first();
            \Illuminate\Support\Facades\Log::info('First train data', [
                'number' => $firstTrain['number'],
                'departure' => $firstTrain['departure'],
                'departure_platform' => $firstTrain['departure_platform'],
                'arrival_platform' => $firstTrain['arrival_platform'],
                'route_long_name' => $firstTrain['route_long_name'],
                'status' => $firstTrain['status']
            ]);
        }
    }

    public function getTrainsQuery()
    {
        $today = Carbon::now()->format('Y-m-d');
        $currentTime = Carbon::now()->format('H:i:s');
        $endTime = Carbon::now()->endOfDay()->format('H:i:s');

        return GtfsTrip::query()
            ->select([
                'gtfs_trips.trip_short_name as number',
                'gtfs_trips.trip_id',
                'gtfs_trips.service_id',
                'departure_stop.departure_time as departure',
                'arrival_stop.arrival_time as arrival',
                'gtfs_routes.route_long_name',
                'gtfs_trips.trip_headsign as destination',
                'train_statuses.status as train_status',
                'statuses.status as status_text',
                'statuses.color_rgb',
                'departure_station.platform_code as departure_platform',
                'arrival_station.platform_code as arrival_platform'
            ])
            ->join('gtfs_stop_times as departure_stop', function ($join) {
                $join->on('gtfs_trips.trip_id', '=', 'departure_stop.trip_id')
                    ->where('departure_stop.stop_sequence', '=', 1);
            })
            ->join('gtfs_stops as departure_station', 'departure_stop.stop_id', '=', 'departure_station.stop_id')
            ->join('gtfs_stop_times as arrival_stop', function ($join) {
                $join->on('gtfs_trips.trip_id', '=', 'arrival_stop.trip_id')
                    ->whereRaw('arrival_stop.stop_sequence = (
                        SELECT MAX(stop_sequence) 
                        FROM gtfs_stop_times 
                        WHERE trip_id = gtfs_trips.trip_id
                    )');
            })
            ->join('gtfs_stops as arrival_station', 'arrival_stop.stop_id', '=', 'arrival_station.stop_id')
            ->join('gtfs_routes', 'gtfs_trips.route_id', '=', 'gtfs_routes.route_id')
            ->leftJoin('train_statuses', 'gtfs_trips.trip_id', '=', 'train_statuses.trip_id')
            ->leftJoin('statuses', 'train_statuses.status', '=', 'statuses.status')
            ->whereIn('gtfs_trips.route_id', $this->selectedRoutes)
            ->where('departure_stop.departure_time', '>=', $currentTime)
            ->where('departure_stop.departure_time', '<=', $endTime)
            ->orderBy('departure_stop.departure_time');
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