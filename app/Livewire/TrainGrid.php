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
        $now = now();
        $startTime = $now->copy()->subHours(2);
        $endTime = $now->copy()->addHours(2);

        $selectedRoutes = DB::table('selected_routes')
            ->where('is_active', true)
            ->pluck('route_id')
            ->toArray();

        Log::info('Selected Routes', ['routes' => $selectedRoutes]);

        if (empty($selectedRoutes)) {
            Log::info('No routes selected, returning empty trains array');
            $this->trains = [];
            return;
        }

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
            ->where('departure_stop.departure_time', '>=', $startTime->format('H:i:s'))
            ->where('departure_stop.departure_time', '<=', $endTime->format('H:i:s'))
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('gtfs_calendar_dates')
                    ->whereColumn('gtfs_calendar_dates.service_id', 'gtfs_trips.service_id')
                    ->where('gtfs_calendar_dates.date', now()->format('Y-m-d'))
                    ->where('gtfs_calendar_dates.exception_type', 1);
            })
            ->orderBy('departure_stop.departure_time')
            ->get();

        Log::info('Found Trains', [
            'count' => $trains->count(),
            'routes' => $trains->pluck('route_id')->unique()->values()->toArray()
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

    public function render()
    {
        return view('livewire.train-grid');
    }
}
