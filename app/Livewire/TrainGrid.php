<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\GtfsTrip;
use App\Models\GtfsCalendarDate;
use App\Models\GtfsStopTime;
use App\Models\TrainStatus;
use App\Models\Status;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class TrainGrid extends Component
{
    public $trains = [];
    public $selectedTrain = null;
    public $newDepartureTime = '';
    public $status = 'on-time';
    public $statuses = [];

    protected $listeners = [
        'refreshTrains' => 'loadTrains',
        'updateTrainStatus' => 'updateTrainStatus'
    ];

    public function mount()
    {
        $this->loadTrains();
        $this->statuses = Status::orderBy('status')->get();
    }

    public function loadTrains()
    {
        $today = Carbon::now()->format('Y-m-d');
        $currentTime = Carbon::now()->format('H:i:s');

        $trains = GtfsTrip::query()
            ->join('gtfs_calendar_dates', 'gtfs_trips.service_id', '=', 'gtfs_calendar_dates.service_id')
            ->join('gtfs_stop_times', 'gtfs_trips.trip_id', '=', 'gtfs_stop_times.trip_id')
            ->join('gtfs_routes', 'gtfs_trips.route_id', '=', 'gtfs_routes.route_id')
            ->leftJoin('train_statuses', 'gtfs_trips.trip_id', '=', 'train_statuses.trip_id')
            ->leftJoin('statuses', 'train_statuses.status', '=', 'statuses.status')
            ->whereIn('gtfs_trips.route_id', function($query) {
                $query->select('route_id')
                    ->from('selected_routes')
                    ->where('is_active', true);
            })
            ->whereDate('gtfs_calendar_dates.date', $today)
            ->where('gtfs_calendar_dates.exception_type', 1)
            ->where('gtfs_stop_times.stop_sequence', 1)
            ->where('gtfs_stop_times.departure_time', '>=', $currentTime)
            ->select([
                'gtfs_trips.trip_short_name as number',
                'gtfs_trips.trip_id',
                'gtfs_trips.service_id',
                'gtfs_stop_times.departure_time as departure',
                'gtfs_routes.route_long_name',
                'gtfs_trips.trip_headsign as destination',
                'train_statuses.status as train_status',
                'statuses.color as status_color'
            ])
            ->orderBy('gtfs_stop_times.departure_time')
            ->limit(6)
            ->get();

        $this->trains = $trains->map(function ($train) {
            $status = $train->train_status ? ucfirst($train->train_status) : 'On-time';
            return [
                'number' => $train->number,
                'trip_id' => $train->trip_id,
                'departure' => substr($train->departure, 0, 5),
                'route_name' => $train->route_long_name,
                'destination' => $train->destination,
                'status' => $status,
                'status_color' => $train->status_color ?? 'gray'
            ];
        })->toArray();
    }

    public function updateTrainStatus(string $trainNumber, string $status, ?string $newTime = null)
    {
        $train = GtfsTrip::where('trip_short_name', $trainNumber)->first();
        
        if (!$train) {
            Log::error('Train not found', ['trainNumber' => $trainNumber]);
            return;
        }

        TrainStatus::updateOrCreate(
            ['trip_id' => $train->trip_id],
            ['status' => strtolower($status)]
        );

        if ($status === 'delayed' && $newTime) {
            $newTimeWithSeconds = $newTime . ':00';
            GtfsStopTime::where('trip_id', $train->trip_id)
                ->where('stop_sequence', 1)
                ->update(['departure_time' => $newTimeWithSeconds]);
        }

        $this->loadTrains();
    }

    public function render()
    {
        return view('livewire.train-grid');
    }
}
