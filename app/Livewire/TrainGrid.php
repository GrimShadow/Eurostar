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
use Illuminate\Support\Facades\Cache;
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
    public $selectedDate;

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
        $this->selectedDate = now()->format('Y-m-d');
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
            // Create a cache key based on group, selected date, and current time (rounded to nearest minute)
            $cacheKey = "train_grid_group_{$this->group->id}_date_{$this->selectedDate}_" . now()->format('H:i');
            
            // Cache the expensive query result for 5 minutes
            $this->trains = Cache::remember($cacheKey, now()->addMinutes(5), function () {
                return $this->getTrainsData();
            });

        } catch (\Exception $e) {
            Log::error('TrainGrid - Error loading trains:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->trains = [];
        }
    }

    private function getTrainsData()
    {
            // Get both API routes and group-specific routes
            $apiRoutes = DB::table('selected_routes')
                ->where('is_active', true)
                ->pluck('route_id')
                ->toArray();

            $groupRoutes = $this->group->selectedRoutes()
                ->where('is_active', true)
                ->pluck('route_id')
                ->toArray();

            // Combine both sets of routes
            $selectedRoutes = array_unique(array_merge($apiRoutes, $groupRoutes));

            // Set time range based on selected date
            $isToday = $this->selectedDate === now()->format('Y-m-d');
            $currentTime = $isToday ? now()->subMinutes(30)->format('H:i:s') : '00:00:00';
            $endTime = '23:59:59';

                // Get unique trips for today with optimized query
        $uniqueTrips = DB::table('gtfs_trips')
            ->select([
                'gtfs_trips.trip_id',
                'gtfs_trips.route_id',
                'gtfs_trips.trip_short_name',
                DB::raw('SUBSTRING_INDEX(gtfs_trips.trip_id, "-", 1) as train_number'),
                'gtfs_routes.route_long_name',
                'gtfs_routes.route_color',
                'gtfs_trips.trip_headsign'
            ])
                ->join('gtfs_routes', 'gtfs_trips.route_id', '=', 'gtfs_routes.route_id')
                ->whereIn('gtfs_trips.route_id', $selectedRoutes)
                ->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('gtfs_calendar_dates')
                        ->whereColumn('gtfs_calendar_dates.service_id', 'gtfs_trips.service_id')
                        ->where('gtfs_calendar_dates.date', $this->selectedDate)
                        ->where('gtfs_calendar_dates.exception_type', 1);
                })
                ->whereExists(function ($query) use ($currentTime, $endTime) {
                    $query->select(DB::raw(1))
                        ->from('gtfs_stop_times')
                        ->whereColumn('gtfs_stop_times.trip_id', 'gtfs_trips.trip_id')
                        ->where('gtfs_stop_times.departure_time', '>=', $currentTime)
                        ->where('gtfs_stop_times.departure_time', '<=', $endTime);
                })
                ->groupBy('gtfs_trips.trip_id', 'gtfs_trips.route_id', 'gtfs_trips.trip_short_name', 
                         'gtfs_routes.route_long_name', 'gtfs_routes.route_color', 'gtfs_trips.trip_headsign')
            ->orderBy('gtfs_trips.trip_short_name')
            ->limit(200) // Add limit to prevent excessive memory usage
                ->get();

        $trains = [];

            foreach ($uniqueTrips as $uniqueTrip) {
                // Get all stops for this trip
                $stops = DB::table('gtfs_stop_times')
                    ->join('gtfs_stops', 'gtfs_stop_times.stop_id', '=', 'gtfs_stops.stop_id')
                    ->where('gtfs_stop_times.trip_id', $uniqueTrip->trip_id)
                    ->whereIn('gtfs_stop_times.stop_id', $this->selectedStations[$uniqueTrip->route_id] ?? [])
                    ->orderBy('gtfs_stop_times.stop_sequence')
                    ->select([
                        'gtfs_stop_times.stop_id',
                        'gtfs_stops.stop_name',
                        'gtfs_stop_times.arrival_time',
                        'gtfs_stop_times.departure_time',
                        'gtfs_stop_times.stop_sequence',
                        'gtfs_stops.platform_code',
                        'gtfs_stop_times.new_departure_time'
                    ])
                ->limit(50) // Limit stops per trip
                    ->get();

                if ($stops->isEmpty()) {
                    continue;
                }

                // Get all stop statuses for this trip
                $stopStatuses = StopStatus::where('trip_id', $uniqueTrip->trip_id)
                    ->get()
                    ->keyBy('stop_id');

                // Map the stops to match API format
                $mappedStops = $stops->map(function ($stop) use ($stopStatuses, $uniqueTrip) {
                    $stopStatus = $stopStatuses->get($stop->stop_id);
                    $status = Status::where('status', $stopStatus?->status ?? 'on-time')->first();
                    
                    return [
                        'stop_id' => $stop->stop_id,
                        'stop_name' => $stop->stop_name,
                        'arrival_time' => substr($stop->arrival_time, 0, 5),
                        'departure_time' => substr($stop->departure_time, 0, 5),
                        'new_departure_time' => $stop->new_departure_time ? substr($stop->new_departure_time, 0, 5) : null,
                        'stop_sequence' => $stop->stop_sequence,
                        'status' => $stopStatus?->status ?? 'on-time',
                        'status_color' => $status?->color_rgb ?? '156,163,175',
                        'status_color_hex' => $this->rgbToHex($status?->color_rgb ?? '156,163,175'),
                        'departure_platform' => $this->getPlatformCode($stop->stop_id, $stop->platform_code, $stopStatus?->departure_platform, $uniqueTrip->trip_id),
                        'arrival_platform' => $this->getPlatformCode($stop->stop_id, $stop->platform_code, $stopStatus?->arrival_platform, $uniqueTrip->trip_id),
                        'check_in_time' => 90
                    ];
                })->values()->toArray();

                // Get the status for the first stop, preferring amsterdam_centraal over amsterdam_centraal_15
                $firstStop = $stops->first();
                $firstStopStatus = null;
                
                // If the first stop is amsterdam_centraal_15, check if we have a status for amsterdam_centraal
                if (str_ends_with($firstStop->stop_id, '_15')) {
                    $baseStopId = str_replace('_15', '', $firstStop->stop_id);
                    $baseStopStatus = $stopStatuses->get($baseStopId);
                    $firstStopStatus = $baseStopStatus ?? $stopStatuses->get($firstStop->stop_id);
                } else {
                    $firstStopStatus = $stopStatuses->get($firstStop->stop_id);
                }

                $firstStopStatusObj = Status::where('status', $firstStopStatus?->status ?? 'on-time')->first();

                // Create the train entry matching API format
            $trains[] = [
                    'number' => $uniqueTrip->trip_short_name,
                    'trip_id' => $uniqueTrip->trip_id,
                    'departure' => substr($stops->first()->departure_time, 0, 5),
                    'arrival_time' => substr($stops->first()->arrival_time, 0, 5),
                    'departure_time' => substr($stops->first()->departure_time, 0, 5),
                    'new_departure_time' => $stops->first()->new_departure_time ? substr($stops->first()->new_departure_time, 0, 5) : null,
                    'route_name' => $uniqueTrip->route_long_name,
                    'route_short_name' => $uniqueTrip->trip_short_name,
                    'train_id' => $uniqueTrip->trip_headsign,
                    'status' => ucfirst($firstStopStatus?->status ?? 'on-time'),
                    'status_color' => $firstStopStatusObj?->color_rgb ?? '156,163,175',
                    'status_color_hex' => $this->rgbToHex($firstStopStatusObj?->color_rgb ?? '156,163,175'),
                    'departure_platform' => $mappedStops[0]['departure_platform'] ?? 'TBD',
                    'arrival_platform' => $mappedStops[count($mappedStops) - 1]['arrival_platform'] ?? 'TBD',
                    'stop_name' => $stops->first()->stop_name,
                    'stops' => $mappedStops
                ];
            }

            // Sort trains by departure time
        usort($trains, function ($a, $b) {
                return strtotime($a['departure']) - strtotime($b['departure']);
            });

        return $trains;
    }

    public function updatedSelectedDate($value)
    {
        $this->selectedDate = $value;
        
        // Clear cache when date changes
        Cache::forget("train_grid_group_{$this->group->id}_date_{$this->selectedDate}_" . now()->format('H:i'));
        
        $this->loadTrains();
    }

    private function getPlatformCode($stopId, $platformCode, $manualPlatform, $tripId = null)
    {
        // If we have a platform code from the stop, use it
        if (!empty($platformCode)) {
            return $platformCode;
        }
        
        // If we have a manually set platform, use it
        if (!empty($manualPlatform) && $manualPlatform !== 'TBD') {
            return $manualPlatform;
        }
        
        // Check for platform assignments in the train_platform_assignments table
        if ($tripId) {
            $platformAssignment = DB::table('train_platform_assignments')
                ->where('trip_id', $tripId)
                ->where('stop_id', $stopId)
                ->first();
            
            if ($platformAssignment && !empty($platformAssignment->platform_code) && $platformAssignment->platform_code !== 'TBD') {
                return $platformAssignment->platform_code;
            }
        }
        
        // If the stop ID is a base stop (like amsterdam_centraal), look up the actual platform from GTFS data
        if (strpos($stopId, '_') === false || !preg_match('/_\d+[a-z]?$/', $stopId)) {
            // If the stop ID is a base stop (like amsterdam_centraal), look up available platform codes
            // from the platform-specific stops in the GTFS data
            $platformStops = DB::table('gtfs_stops')
                ->where('stop_id', 'LIKE', $stopId . '_%')
                ->whereNotNull('platform_code')
                ->where('platform_code', '!=', '')
                ->orderBy('platform_code')
                ->pluck('platform_code');
            
            if ($platformStops->isNotEmpty()) {
                // Return the first available platform code
                return $platformStops->first();
            }
            
            return 'TBD';
        }
        
        return 'TBD';
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
            ->select([
                DB::raw('MIN(gtfs_stops.stop_name) as name'),
                'gtfs_stop_times.stop_sequence as sequence',
                DB::raw('MIN(gtfs_stop_times.arrival_time) as arrival'),
                DB::raw('MIN(gtfs_stop_times.departure_time) as departure')
            ])
            ->groupBy('gtfs_stop_times.stop_sequence')
            ->orderBy('gtfs_stop_times.stop_sequence')
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
            // Clear cache to ensure instant updates are visible
            $this->clearTrainGridCache();
            
            // Get the current stop ID from the selected train
            $train = collect($this->trains)->first(function ($train) use ($tripId) {
                return $train['trip_id'] === $tripId;
            });

            if (!$train) {
                Log::error('Train not found in local state', ['trip_id' => $tripId]);
                return;
            }

            // Get the first stop from the stops array
            $firstStop = $train['stops'][0] ?? null;
            if (!$firstStop) {
                Log::error('No stops found for train', ['trip_id' => $tripId]);
                return;
            }

            // Get the status object to get the color information
            $statusObj = Status::where('status', $status)->first();
            if (!$statusObj) {
                Log::error('Status not found', ['status' => $status]);
                return;
            }

            // Find all stops with the same name and departure time
            $matchingStops = DB::table('gtfs_stop_times')
                ->join('gtfs_stops', 'gtfs_stop_times.stop_id', '=', 'gtfs_stops.stop_id')
                ->where('gtfs_stop_times.trip_id', $tripId)
                ->where(function ($query) use ($firstStop) {
                    $query->where('gtfs_stops.stop_name', $firstStop['stop_name'])
                        ->orWhere('gtfs_stop_times.stop_id', 'like', $firstStop['stop_id'] . '%')
                        ->orWhere('gtfs_stop_times.stop_id', 'like', str_replace('_15', '', $firstStop['stop_id']) . '%');
                })
                ->where('gtfs_stop_times.departure_time', $firstStop['departure_time'] . ':00')
                ->select('gtfs_stop_times.stop_id', 'gtfs_stop_times.new_departure_time')
                ->get();


            // Update each matching stop
            foreach ($matchingStops as $matchingStop) {

                // Update the platform if provided
                if ($platform) {
                    $this->updatePlatform($tripId, $matchingStop->stop_id, $platform);
                }

                // Update the stop status with color information
                $stopStatus = StopStatus::updateOrCreate(
                    [
                        'trip_id' => $tripId,
                        'stop_id' => $matchingStop->stop_id
                    ],
                    [
                        'status' => $status,
                        'status_color' => $statusObj->color_rgb,
                        'status_color_hex' => $this->rgbToHex($statusObj->color_rgb),
                        'updated_at' => now()
                    ]
                );


                // Only update the new departure time if it's provided and different from the current one
                if ($newTime && $newTime !== $matchingStop->new_departure_time) {
                    DB::table('gtfs_stop_times')
                        ->where('trip_id', $tripId)
                        ->where('stop_id', $matchingStop->stop_id)
                        ->update(['new_departure_time' => $newTime]);
                }

                // Notify other components for each stop
                $this->dispatch('stop-status-updated', [
                    'trip_id' => $tripId,
                    'stop_id' => $matchingStop->stop_id,
                    'status' => $status
                ]);
            }

            // Reload the trains data (cache is already cleared above)
            $this->loadTrains();

            // Force a refresh of the view
            $this->dispatch('refresh');

        } catch (\Exception $e) {
            Log::error('Error updating train status:', [
                'trip_id' => $tripId,
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
            ->limit(300) // Add limit to prevent excessive data loading
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
        // Clear cache to ensure instant updates are visible
        $this->clearTrainGridCache();
        
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

        // Reload fresh data
        $this->loadTrains();

        $this->dispatch('stop-status-updated', [
            'trip_id' => $tripId,
            'stop_id' => $stopId,
            'status' => $status
        ]);
    }

    /**
     * Clear the train grid cache for instant updates
     */
    private function clearTrainGridCache()
    {
        // Clear current minute cache
        $currentCacheKey = "train_grid_group_{$this->group->id}_date_{$this->selectedDate}_" . now()->format('H:i');
        Cache::forget($currentCacheKey);
        
        // Also clear the previous minute cache in case we're at the boundary
        $previousMinuteCacheKey = "train_grid_group_{$this->group->id}_date_{$this->selectedDate}_" . now()->subMinute()->format('H:i');
        Cache::forget($previousMinuteCacheKey);
        
        // Clear API cache as well to ensure consistency
        $this->clearApiCache();
    }

    /**
     * Clear the API cache for train data
     */
    private function clearApiCache()
    {
        // Clear current 5-minute interval API cache
        $currentApiCacheKey = 'train_api_today_' . now()->format('Y-m-d_H:') . str_pad(floor(now()->minute / 5) * 5, 2, '0', STR_PAD_LEFT);
        Cache::forget($currentApiCacheKey);
        
        // Also clear the previous 5-minute interval in case we're at the boundary
        $previousApiCacheKey = 'train_api_today_' . now()->subMinutes(5)->format('Y-m-d_H:') . str_pad(floor(now()->subMinutes(5)->minute / 5) * 5, 2, '0', STR_PAD_LEFT);
        Cache::forget($previousApiCacheKey);
    }

    public function render()
    {
        return view('livewire.train-grid');
    }
}