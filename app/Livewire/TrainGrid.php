<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\GtfsTrip;
use App\Models\GtfsCalendarDate;
use App\Models\GtfsStopTime;
use App\Models\GtfsStop;
use App\Models\TrainStatus;
use App\Models\Status;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TrainGrid extends Component
{
    public $trains = [];
    public $selectedTrain = null;
    public $newDepartureTime = '';
    public $status = 'on-time';
    public $statuses = [];
    public $routeStops = [];

    protected $listeners = [
        'refreshTrains' => 'loadTrains',
        'updateTrainStatus' => 'updateTrainStatus',
        'echo:train-statuses,TrainStatusUpdated' => 'handleTrainStatusUpdated'
    ];

    public function handleTrainStatusUpdated($event)
    {
        $this->loadTrains();
    }

    public function mount()
    {
        $this->loadTrains();
        $this->statuses = Status::orderBy('status')->get();
    }

    public function loadTrains()
    {
        $today = Carbon::now()->format('Y-m-d');
        $currentTime = Carbon::now()->format('H:i:s');
        $fourHoursLater = Carbon::now()->addHours(4)->format('H:i:s');

        // Clear any cached data
        DB::flushQueryLog();
        DB::enableQueryLog();

        // First get all the departure stop times for the time window
        $departureStopTimes = DB::table('gtfs_stop_times as departure_stop')
            ->join('gtfs_trips', 'gtfs_trips.trip_id', '=', 'departure_stop.trip_id')
            ->join('gtfs_calendar_dates', 'gtfs_trips.service_id', '=', 'gtfs_calendar_dates.service_id')
            ->whereIn('gtfs_trips.route_id', function($query) {
                $query->select('route_id')
                    ->from('selected_routes')
                    ->where('is_active', true);
            })
            ->whereDate('gtfs_calendar_dates.date', $today)
            ->where('gtfs_calendar_dates.exception_type', 1)
            ->where('departure_stop.stop_sequence', 1) // Only get first stops
            ->where(function($query) use ($currentTime, $fourHoursLater) {
                $query->where('departure_stop.departure_time', '>=', $currentTime)
                      ->where('departure_stop.departure_time', '<=', $fourHoursLater);
                
                // Handle case when 4-hour window crosses midnight
                if ($fourHoursLater < $currentTime) {
                    $query->orWhere('departure_stop.departure_time', '<=', $fourHoursLater);
                }
            })
            ->select('gtfs_trips.trip_id')
            ->pluck('trip_id');

        // Then get the complete train information for these trips
        $this->trains = GtfsTrip::query()
            ->join('gtfs_calendar_dates', 'gtfs_trips.service_id', '=', 'gtfs_calendar_dates.service_id')
            ->join('gtfs_stop_times as departure_stop', function($join) {
                $join->on('gtfs_trips.trip_id', '=', 'departure_stop.trip_id')
                    ->where('departure_stop.stop_sequence', 1);
            })
            ->join('gtfs_stops as departure_station', 'departure_stop.stop_id', '=', 'departure_station.stop_id')
            ->join('gtfs_stop_times as arrival_stop', function($join) {
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
            ->whereIn('gtfs_trips.trip_id', $departureStopTimes)
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
                'statuses.color',
                'departure_station.platform_code as departure_platform',
                'arrival_station.platform_code as arrival_platform'
            ])
            ->orderBy('departure_stop.departure_time')
            ->limit(6)
            ->get()
            ->map(function ($train) {
                return [
                    'number' => $train->number,
                    'trip_id' => $train->trip_id,
                    'departure' => substr($train->departure, 0, 5),
                    'arrival' => substr($train->arrival, 0, 5),
                    'route_name' => $train->route_long_name,
                    'destination' => $train->destination,
                    'status' => ucfirst($train->status_text ?? $train->train_status ?? 'on-time'),
                    'status_color' => $train->color ?? 'gray',
                    'departure_platform' => $train->departure_platform ?? 'TBD',
                    'arrival_platform' => $train->arrival_platform ?? 'TBD'
                ];
            })
            ->toArray();

        Log::info('Train Statuses', ['trains' => $this->trains]);
    }

    public function loadRouteStops($tripId)
    {
        $this->routeStops = GtfsStopTime::query()
            ->join('gtfs_stops', 'gtfs_stop_times.stop_id', '=', 'gtfs_stops.stop_id')
            ->where('gtfs_stop_times.trip_id', $tripId)
            ->orderBy('gtfs_stop_times.stop_sequence')
            ->select([
                'gtfs_stops.stop_name',
                'gtfs_stop_times.arrival_time',
                'gtfs_stop_times.departure_time',
                'gtfs_stop_times.stop_sequence'
            ])
            ->get()
            ->map(function ($stop) {
                return [
                    'name' => $stop->stop_name,
                    'arrival' => substr($stop->arrival_time, 0, 5),
                    'departure' => substr($stop->departure_time, 0, 5),
                    'sequence' => $stop->stop_sequence
                ];
            })
            ->toArray();
    }

    public function updateTrainStatus(string $trainNumber, string $status, ?string $newTime = null)
    {
        $today = Carbon::now()->format('Y-m-d');
        
        $train = GtfsTrip::query()
            ->join('gtfs_calendar_dates', 'gtfs_trips.service_id', '=', 'gtfs_calendar_dates.service_id')
            ->where('gtfs_trips.trip_short_name', $trainNumber)
            ->whereDate('gtfs_calendar_dates.date', $today)
            ->where('gtfs_calendar_dates.exception_type', 1)
            ->first();
        
        if (!$train) {
            Log::error('Train not found', ['trainNumber' => $trainNumber, 'date' => $today]);
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
        $this->dispatch('refresh');
    }

    public function render()
    {
        return view('livewire.train-grid');
    }
}
