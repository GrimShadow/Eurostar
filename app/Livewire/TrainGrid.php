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
use Illuminate\Support\Facades\Auth;
use App\Models\Group;
use App\Models\GroupRouteStation;

class TrainGrid extends Component
{
    public $trains = [];
    public $selectedTrain = null;
    public $newDepartureTime = '';
    public $status = 'on-time';
    public $statuses = [];
    public $routeStops = [];
    public $group;
    public $selectedStations = [];

    protected $listeners = [
        'refreshTrains' => 'loadTrains',
        'updateTrainStatus' => 'updateTrainStatus',
        'echo:train-statuses,TrainStatusUpdated' => 'handleTrainStatusUpdated'
    ];

    public function handleTrainStatusUpdated($event)
    {
        $this->loadTrains();
    }

    public function mount(Group $group)
    {
        $this->group = $group;
        $this->loadSelectedStations();
        $this->loadTrains();
        $this->statuses = Status::orderBy('status')->get();
    }

    public function loadSelectedStations()
    {
        $this->selectedStations = $this->group->routeStations()
            ->where('is_active', true)
            ->get()
            ->groupBy('route_id')
            ->map(function ($stations) {
                return $stations->pluck('stop_id')->toArray();
            })
            ->toArray();
    }

    public function loadTrains()
    {
        if (!$this->group) {
            Log::info('TrainGrid - No group provided');
            $this->trains = [];
            return;
        }

        $selectedRoutes = $this->group->selectedRoutes()
            ->where('is_active', true)
            ->pluck('route_id')
            ->toArray();

        Log::info('TrainGrid - Debug Info', [
            'group_id' => $this->group->id,
            'selected_routes' => $selectedRoutes,
            'total_routes' => DB::table('gtfs_routes')->count(),
            'total_trips' => DB::table('gtfs_trips')->count(),
            'total_stop_times' => DB::table('gtfs_stop_times')->count()
        ]);

        if (empty($selectedRoutes)) {
            Log::info('TrainGrid - No routes selected, returning empty trains array');
            $this->trains = [];
            return;
        }

        $now = Carbon::now();
        $twentyMinutesAgo = $now->copy()->subMinutes(20);

        Log::info('Time Filtering', [
            'current_time' => $now->format('H:i:s'),
            'twenty_minutes_ago' => $twentyMinutesAgo->format('H:i:s')
        ]);

        $trains = GtfsTrip::query()
            ->distinct()
            ->select([
                'gtfs_trips.trip_id',
                'gtfs_trips.route_id',
                'gtfs_trips.service_id',
                'gtfs_trips.trip_headsign',
                'gtfs_trips.direction_id',
                'gtfs_trips.shape_id',
                'gtfs_trips.trip_short_name as number',
                'gtfs_routes.route_short_name',
                'gtfs_routes.route_long_name',
                'gtfs_routes.route_type',
                'gtfs_routes.route_color',
                'gtfs_routes.route_text_color',
                'departure_stop.stop_id as departure_stop_id',
                'departure_stop.departure_time',
                'arrival_stop.stop_id as arrival_stop_id',
                'arrival_stop.arrival_time',
                'train_platform_assignments.platform_code as departure_platform',
                'arrival_platform_assignments.platform_code as arrival_platform',
                'train_statuses.status as train_status',
                'statuses.status as status_text',
                'statuses.color_rgb as status_color'
            ])
            ->join('gtfs_routes', 'gtfs_trips.route_id', '=', 'gtfs_routes.route_id')
            ->join('gtfs_stop_times as departure_stop', function ($join) {
                $join->on('gtfs_trips.trip_id', '=', 'departure_stop.trip_id')
                    ->where('departure_stop.stop_sequence', '=', function ($query) {
                        $query->select('stop_sequence')
                            ->from('gtfs_stop_times')
                            ->whereColumn('trip_id', 'gtfs_trips.trip_id')
                            ->orderBy('stop_sequence')
                            ->limit(1);
                    });
            })
            ->join('gtfs_stop_times as arrival_stop', function ($join) {
                $join->on('gtfs_trips.trip_id', '=', 'arrival_stop.trip_id')
                    ->where('arrival_stop.stop_sequence', '=', function ($query) {
                        $query->select('stop_sequence')
                            ->from('gtfs_stop_times')
                            ->whereColumn('trip_id', 'gtfs_trips.trip_id')
                            ->orderByDesc('stop_sequence')
                            ->limit(1);
                    });
            })
            ->leftJoin('train_platform_assignments', function ($join) {
                $join->on('gtfs_trips.trip_id', '=', 'train_platform_assignments.trip_id')
                    ->on('departure_stop.stop_id', '=', 'train_platform_assignments.stop_id');
            })
            ->leftJoin('train_platform_assignments as arrival_platform_assignments', function ($join) {
                $join->on('gtfs_trips.trip_id', '=', 'arrival_platform_assignments.trip_id')
                    ->on('arrival_stop.stop_id', '=', 'arrival_platform_assignments.stop_id');
            })
            ->leftJoin('train_statuses', 'gtfs_trips.trip_id', '=', 'train_statuses.trip_id')
            ->leftJoin('statuses', 'train_statuses.status', '=', 'statuses.status')
            ->whereIn('gtfs_trips.route_id', $selectedRoutes)
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('gtfs_calendar_dates')
                    ->whereColumn('gtfs_calendar_dates.service_id', 'gtfs_trips.service_id')
                    ->where('gtfs_calendar_dates.date', now()->format('Y-m-d'))
                    ->where('gtfs_calendar_dates.exception_type', 1);
            })
            ->where(function($query) use ($now, $twentyMinutesAgo) {
                // Show trains that departed in the last 20 minutes
                $query->whereRaw('TIME(departure_stop.departure_time) BETWEEN TIME(?) AND TIME(?)', [
                    $twentyMinutesAgo->format('H:i:s'),
                    $now->format('H:i:s')
                ])
                // OR show future trains
                ->orWhereRaw('TIME(departure_stop.departure_time) > TIME(?)', [$now->format('H:i:s')]);
            })
            ->orderBy('departure_stop.departure_time')
            ->get();

        Log::info('Found Trains', [
            'count' => $trains->count(),
            'routes' => $trains->pluck('route_id')->unique()->values()->toArray(),
            'departure_times' => $trains->pluck('departure_time')->toArray()
        ]);

        $this->trains = $trains->map(function ($train) {
            return [
                'number' => $train->number,
                'trip_id' => $train->trip_id,
                'route_id' => $train->route_id,
                'service_id' => $train->service_id,
                'destination' => $train->trip_headsign,
                'direction_id' => $train->direction_id,
                'shape_id' => $train->shape_id,
                'route_short_name' => $train->route_short_name,
                'route_name' => $train->route_long_name,
                'route_type' => $train->route_type,
                'route_color' => $train->route_color,
                'route_text_color' => $train->route_text_color,
                'departure_stop_id' => $train->departure_stop_id,
                'departure' => substr($train->departure_time, 0, 5),
                'arrival_stop_id' => $train->arrival_stop_id,
                'arrival' => substr($train->arrival_time, 0, 5),
                'departure_platform' => $train->departure_platform ?? 'TBD',
                'arrival_platform' => $train->arrival_platform ?? 'TBD',
                'status' => ucfirst($train->status_text ?? $train->train_status ?? 'on-time'),
                'status_color' => $train->status_color ?? '156,163,175'
            ];
        });
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

    public function updateTrainStatus(string $trainNumber, string $status, ?string $newTime = null, ?string $platform = null)
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

        if ($platform) {
            // Get the departure stop for this specific train
            $departureStop = GtfsStopTime::where('trip_id', $train->trip_id)
                ->where('stop_sequence', 1)
                ->first();

            if ($departureStop) {
                // Create or update the platform assignment for this specific train and stop
                DB::table('train_platform_assignments')->updateOrInsert(
                    [
                        'trip_id' => $train->trip_id,
                        'stop_id' => $departureStop->stop_id
                    ],
                    [
                        'platform_code' => $platform,
                        'updated_at' => now()
                    ]
                );
            }
        }

        $this->loadTrains();
        $this->dispatch('refresh');
    }

    public function getTrains()
    {
        $this->trains = [];

        // Get all active routes for this group
        $activeRoutes = $this->group->selectedRoutes()
            ->where('is_active', true)
            ->pluck('route_id')
            ->toArray();

        if (empty($activeRoutes)) {
            return;
        }

        $currentTime = now()->format('H:i:s');

        // Get all trips for the active routes
        $trips = GtfsTrip::whereIn('route_id', $activeRoutes)
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('gtfs_calendar_dates')
                    ->whereColumn('gtfs_calendar_dates.service_id', 'gtfs_trips.service_id')
                    ->where('gtfs_calendar_dates.date', now()->format('Y-m-d'))
                    ->where('gtfs_calendar_dates.exception_type', 1);
            })
            ->get();

        foreach ($trips as $trip) {
            // Get stop times for this trip at the selected stations
            $stopTimes = GtfsStopTime::where('trip_id', $trip->trip_id)
                ->whereIn('stop_id', $this->selectedStations[$trip->route_id] ?? [])
                ->where('departure_time', '>=', $currentTime)
                ->orderBy('stop_sequence')
                ->get();

            if ($stopTimes->isEmpty()) {
                continue;
            }

            foreach ($stopTimes as $stopTime) {
                $this->trains[] = [
                    'trip_id' => $trip->trip_id,
                    'route_id' => $trip->route_id,
                    'route_short_name' => $trip->route->route_short_name,
                    'route_long_name' => $trip->route->route_long_name,
                    'route_color' => $trip->route->route_color,
                    'stop_id' => $stopTime->stop_id,
                    'stop_name' => $stopTime->stop->stop_name,
                    'arrival_time' => $stopTime->arrival_time,
                    'departure_time' => $stopTime->departure_time,
                    'platform_code' => $stopTime->stop->platform_code,
                ];
            }
        }

        // Sort trains by departure time
        usort($this->trains, function ($a, $b) {
            return strtotime($a['departure_time']) - strtotime($b['departure_time']);
        });
    }

    public function render()
    {
        $this->getTrains();
        return view('livewire.train-grid');
    }
}
