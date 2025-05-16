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
use App\Models\StopStatus;
use Illuminate\Support\Facades\Http;

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
    public $selectedStops = [];

    protected $listeners = [
        'refreshTrains' => 'loadTrains',
        'updateTrainStatus' => 'updateTrainStatus',
        'echo:train-statuses,TrainStatusUpdated' => 'handleTrainStatusUpdated',
        'updateStopStatus' => 'updateStopStatus'
    ];

    public function handleTrainStatusUpdated($event)
    {
        $this->loadTrains();
    }

    public function mount(Group $group)
    {
        $this->group = $group;
        $this->loadSelectedStations();
        $this->statuses = Status::all();
        $this->loadTrains();
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
        try {
            // Get the selected routes
            $selectedRoutes = DB::table('selected_routes')
                ->where('is_active', true)
                ->pluck('route_id')
                ->toArray();

            // Set time range from now until end of day
            $currentTime = now()->format('H:i:s');
            $endTime = '23:59:59';

            // Get unique trips for today
            $uniqueTrips = GtfsTrip::query()
                ->whereIn('route_id', $selectedRoutes)
                ->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('gtfs_calendar_dates')
                        ->whereColumn('gtfs_calendar_dates.service_id', 'gtfs_trips.service_id')
                        ->where('gtfs_calendar_dates.date', now()->format('Y-m-d'))
                        ->where('gtfs_calendar_dates.exception_type', 1);
                })
                ->whereExists(function ($query) use ($currentTime, $endTime) {
                    $query->select(DB::raw(1))
                        ->from('gtfs_stop_times')
                        ->whereColumn('gtfs_stop_times.trip_id', 'gtfs_trips.trip_id')
                        ->where('gtfs_stop_times.departure_time', '>=', $currentTime)
                        ->where('gtfs_stop_times.departure_time', '<=', $endTime);
                })
                ->get();

            $this->trains = [];

            foreach ($uniqueTrips as $uniqueTrip) {
                // Get the stop times for this trip at the selected stations
                $stopTimes = GtfsStopTime::where('trip_id', $uniqueTrip->trip_id)
                    ->whereIn('stop_id', $this->selectedStations[$uniqueTrip->route_id] ?? [])
                    ->where('departure_time', '>=', $currentTime)
                    ->where('departure_time', '<=', $endTime)
                    ->orderBy('stop_sequence')
                    ->get();

                if ($stopTimes->isEmpty()) {
                    continue;
                }

                foreach ($stopTimes as $stopTime) {
                    // Get the train status
                    $trainStatus = TrainStatus::where('trip_id', $uniqueTrip->trip_id)->first();
                    
                    // Get the stop status
                    $stopStatus = StopStatus::where('trip_id', $uniqueTrip->trip_id)
                        ->where('stop_id', $stopTime->stop_id)
                        ->first();

                    // Get platform assignment
                    $platformAssignment = DB::table('train_platform_assignments')
                        ->where('trip_id', $uniqueTrip->trip_id)
                        ->where('stop_id', $stopTime->stop_id)
                        ->first();

                    // Use stop status if available, otherwise use train status
                    $currentStatus = $stopStatus?->status ?? $trainStatus?->status ?? 'on-time';

                    // Get the status color
                    $status = Status::where('status', $currentStatus)->first();

                    // Determine platform - use platform assignment first, then stop status, then default to TBD
                    $platform = $platformAssignment?->platform_code ?? 
                               $stopStatus?->departure_platform ?? 
                               $stopStatus?->arrival_platform ?? 
                               'TBD';

                    $this->trains[] = [
                        'trip_id' => $uniqueTrip->trip_id,
                        'route_id' => $uniqueTrip->route_id,
                        'route_short_name' => $uniqueTrip->trip_short_name,
                        'route_long_name' => $stopTime->trip->route->route_long_name,
                        'route_color' => $stopTime->trip->route->route_color,
                        'stop_id' => $stopTime->stop_id,
                        'stop_name' => $stopTime->stop->stop_name,
                        'arrival_time' => $stopTime->arrival_time,
                        'departure_time' => $stopTime->departure_time,
                        'platform_code' => $platform,
                        'status' => $currentStatus,
                        'status_color' => $status?->color_rgb ?? '156,163,175',
                        'status_color_hex' => $this->rgbToHex($status?->color_rgb ?? '156,163,175'),
                        'departure_platform' => $platform,
                        'arrival_platform' => $platform
                    ];
                }
            }

            // Sort trains by departure time
            usort($this->trains, function ($a, $b) {
                return strtotime($a['departure_time']) - strtotime($b['departure_time']);
            });

        } catch (\Exception $e) {
            Log::error('TrainGrid - Error loading trains:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->trains = [];
        }
    }

    private function rgbToHex($rgb)
    {
        if (empty($rgb)) {
            return '#9CA3AF'; // Default gray color
        }

        $rgbArray = explode(',', $rgb);
        if (count($rgbArray) !== 3) {
            return '#9CA3AF'; // Default gray color if invalid format
        }

        $hex = '#';
        foreach ($rgbArray as $component) {
            $hex .= str_pad(dechex(trim($component)), 2, '0', STR_PAD_LEFT);
        }
        return strtoupper($hex);
    }

    public function loadRouteStops($tripId)
    {
        $stops = DB::table('gtfs_stop_times')
            ->join('gtfs_stops', 'gtfs_stop_times.stop_id', '=', 'gtfs_stops.stop_id')
            ->where('gtfs_stop_times.trip_id', $tripId)
            ->orderBy('gtfs_stop_times.stop_sequence')
            ->select([
                'gtfs_stops.stop_name as name',
                'gtfs_stop_times.stop_sequence as sequence',
                'gtfs_stop_times.arrival_time as arrival',
                'gtfs_stop_times.departure_time as departure'
            ])
            ->get();

        $this->routeStops = $stops->map(function ($stop) {
            return [
                'name' => $stop->name,
                'sequence' => $stop->sequence,
                'arrival' => substr($stop->arrival, 0, 5),
                'departure' => substr($stop->departure, 0, 5)
            ];
        })->toArray();
    }

    private function updatePlatform($tripId, $stopId, $platform)
    {
        if (!$platform) {
            return;
        }

        // Update train_platform_assignments table
        DB::table('train_platform_assignments')->updateOrInsert(
            [
                'trip_id' => $tripId,
                'stop_id' => $stopId,
            ],
            [
                'platform_code' => $platform,
                'updated_at' => now()
            ]
        );

        // Update stop_statuses table
        StopStatus::updateOrCreate(
            [
                'trip_id' => $tripId,
                'stop_id' => $stopId
            ],
            [
                'departure_platform' => $platform,
                'arrival_platform' => $platform,
                'updated_at' => now()
            ]
        );
    }

    public function updateTrainStatus($tripId, $status, $newTime = null, $platform = null)
    {
        try {
            // Get the current stop ID from the selected train
            $train = collect($this->trains)->first(function ($train) use ($tripId) {
                return $train['trip_id'] === $tripId;
            });

            if (!$train) {
                Log::error('Train not found in local state', ['trip_id' => $tripId]);
                return;
            }

            // Update the platform if provided
            if ($platform) {
                $this->updatePlatform($tripId, $train['stop_id'], $platform);
            }

            // Update the stop status
            $stopStatus = StopStatus::updateOrCreate(
                [
                    'trip_id' => $tripId,
                    'stop_id' => $train['stop_id']
                ],
                [
                    'status' => $status,
                    'updated_at' => now()
                ]
            );

            // Update the departure time if provided
            if ($newTime) {
                DB::table('gtfs_stop_times')
                    ->where('trip_id', $tripId)
                    ->where('stop_id', $train['stop_id'])
                    ->update(['departure_time' => $newTime]);
            }

            // Reload the trains data
            $this->loadTrains();

            // Notify other components
            $this->dispatch('stop-status-updated', [
                'trip_id' => $tripId,
                'stop_id' => $train['stop_id'],
                'status' => $status
            ]);

            // Force a refresh of the view
            $this->dispatch('refresh');

        } catch (\Exception $e) {
            Log::error('Error updating stop status:', [
                'trip_id' => $tripId,
                'stop_id' => $train['stop_id'] ?? null,
                'status' => $status,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
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

    public function updateStopStatus($tripId, $stopId, $status, $departurePlatform = null, $arrivalPlatform = null)
    {
        $stopStatus = StopStatus::updateOrCreate(
            [
                'trip_id' => $tripId,
                'stop_id' => $stopId,
            ],
            [
                'status' => $status,
                'departure_platform' => $departurePlatform,
                'arrival_platform' => $arrivalPlatform,
            ]
        );

        // Update the local state
        foreach ($this->trains as &$train) {
            if ($train['trip_id'] === $tripId) {
                foreach ($train['stops'] as &$stop) {
                    if ($stop['stop_id'] === $stopId) {
                        $stop['status'] = $status;
                        $stop['departure_platform'] = $departurePlatform;
                        $stop['arrival_platform'] = $arrivalPlatform;
                        break;
                    }
                }
                break;
            }
        }

        $this->dispatch('stop-status-updated', [
            'trip_id' => $tripId,
            'stop_id' => $stopId,
            'status' => $status
        ]);
    }

    public function render()
    {
        return view('livewire.train-grid');
    }
}
