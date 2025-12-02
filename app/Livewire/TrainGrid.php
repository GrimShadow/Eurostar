<?php

namespace App\Livewire;

use App\Models\CheckInStatus;
use App\Models\Group;
use App\Models\GtfsStopTime;
use App\Models\GtfsTrip;
use App\Models\Setting;
use App\Models\Status;
use App\Models\StopStatus;
use App\Models\TrainCheckInStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class TrainGrid extends Component
{
    public $trains = [];

    public $selectedTrain = null;

    public $newDepartureTime = '';

    public $status = 'on-time';

    public $routeStops = [];

    public $group;

    public $selectedStations = [];

    public $selectedStops = [];

    public $selectedDate;

    protected $listeners = [
        'refreshTrains' => 'loadTrains',
        'updateTrainStatus' => 'updateTrainStatus',
        'echo:train-statuses,TrainStatusUpdated' => 'handleTrainStatusUpdated',
        'updateStopStatus' => 'updateStopStatus',
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
            // Use 5-minute cache intervals instead of per-minute for better cache hit rate
            $interval = floor(now()->minute / 5) * 5;
            $cacheKey = "train_grid_group_{$this->group->id}_date_{$this->selectedDate}_{$interval}";

            // Cache the expensive query result for 5 minutes
            $this->trains = Cache::remember($cacheKey, now()->addMinutes(5), function () {
                return $this->getTrainsData();
            });

        } catch (\Exception $e) {
            Log::error('TrainGrid - Error loading trains:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
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

        if ($isToday) {
            // For today, only show trains that haven't departed yet OR departed within the last 30 minutes
            $currentTime = now()->subMinutes(30)->format('H:i:s');
            $endTime = '23:59:59';
        } else {
            // For other dates, show all trains
            $currentTime = '00:00:00';
            $endTime = '23:59:59';
        }

        // Get unique trips for today with optimized query
        $uniqueTrips = DB::table('gtfs_trips')
            ->select([
                'gtfs_trips.trip_id',
                'gtfs_trips.route_id',
                'gtfs_trips.trip_short_name',
                DB::raw('SUBSTRING_INDEX(gtfs_trips.trip_id, "-", 1) as train_number'),
                'gtfs_routes.route_long_name',
                'gtfs_routes.route_color',
                'gtfs_trips.trip_headsign',
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

        if ($uniqueTrips->isEmpty()) {
            return [];
        }

        // OPTIMIZATION: Batch load all data to avoid N+1 queries
        $tripIds = $uniqueTrips->pluck('trip_id')->toArray();

        // Get all selected station IDs across all routes
        $allSelectedStationIds = [];
        foreach ($uniqueTrips as $trip) {
            $routeStations = $this->selectedStations[$trip->route_id] ?? [];
            $allSelectedStationIds = array_merge($allSelectedStationIds, $routeStations);
        }
        $allSelectedStationIds = array_unique($allSelectedStationIds);

        // Batch load all stops for all trips in a single query
        $allStops = DB::table('gtfs_stop_times')
            ->join('gtfs_stops', 'gtfs_stop_times.stop_id', '=', 'gtfs_stops.stop_id')
            ->whereIn('gtfs_stop_times.trip_id', $tripIds)
            ->whereIn('gtfs_stop_times.stop_id', $allSelectedStationIds)
            ->select([
                'gtfs_stop_times.trip_id',
                'gtfs_stop_times.stop_id',
                'gtfs_stops.stop_name',
                'gtfs_stop_times.arrival_time',
                'gtfs_stop_times.departure_time',
                'gtfs_stop_times.stop_sequence',
                'gtfs_stops.platform_code',
                'gtfs_stop_times.new_departure_time',
            ])
            ->orderBy('gtfs_stop_times.trip_id')
            ->orderBy('gtfs_stop_times.stop_sequence')
            ->get()
            ->groupBy('trip_id');

        // Batch load all stop statuses for all trips in a single query
        $allStopStatuses = StopStatus::whereIn('trip_id', $tripIds)
            ->get()
            ->groupBy('trip_id')
            ->map(function ($statuses) {
                return $statuses->keyBy('stop_id');
            });

        // Load all statuses once and cache them
        $allStatuses = Status::all()->keyBy('status');

        // Batch load all train check-in statuses
        $allTrainCheckInStatuses = TrainCheckInStatus::whereIn('trip_id', $tripIds)
            ->with('checkInStatus')
            ->get()
            ->keyBy('trip_id');

        // Load all check-in statuses once and cache them
        $allCheckInStatuses = CheckInStatus::all()->keyBy('id');

        // Load check-in time settings
        $globalCheckInOffset = (int) (Setting::where('key', 'global_check_in_offset')->value('value') ?? 90);
        $specificTrainTimesSetting = Setting::where('key', 'specific_train_check_in_times')->value('value');
        $specificTrainTimes = $specificTrainTimesSetting ? json_decode($specificTrainTimesSetting, true) : [];

        $trains = [];
        $currentTimeObj = now();

        foreach ($uniqueTrips as $uniqueTrip) {
            // Get stops for this trip from the pre-loaded collection
            $stops = $allStops->get($uniqueTrip->trip_id, collect())
                ->filter(function ($stop) use ($uniqueTrip) {
                    // Filter by selected stations for this route
                    return in_array($stop->stop_id, $this->selectedStations[$uniqueTrip->route_id] ?? []);
                })
                ->take(50) // Limit stops per trip
                ->values();

            if ($stops->isEmpty()) {
                continue;
            }

            // For today, check if the train has already departed (and not within the last 30 minutes)
            if ($isToday) {
                $firstStop = $stops->first();

                // Create a full datetime for today with the departure time (using the same timezone as the application)
                $departureDateTime = Carbon::createFromFormat('Y-m-d H:i:s', $currentTimeObj->format('Y-m-d').' '.$firstStop->departure_time, $currentTimeObj->timezone);

                // Check if the train has already departed
                if ($departureDateTime->isPast()) {
                    $minutesSinceDeparture = abs($currentTimeObj->diffInMinutes($departureDateTime, false));

                    // If the train departed more than 30 minutes ago, skip it
                    if ($minutesSinceDeparture > 30) {
                        continue;
                    }
                }
            }

            // Get all stop statuses for this trip from the pre-loaded collection
            $stopStatuses = $allStopStatuses->get($uniqueTrip->trip_id, collect());

            // Determine check-in offset for this train
            $trainNumber = $uniqueTrip->trip_short_name;
            $checkInOffset = (int) ($specificTrainTimes[$trainNumber] ?? $globalCheckInOffset);

            // Map the stops to match API format
            $mappedStops = $stops->map(function ($stop) use ($stopStatuses, $uniqueTrip, $allStatuses, $checkInOffset) {
                $stopStatus = $stopStatuses->get($stop->stop_id);
                $statusKey = $stopStatus?->status ?? 'on-time';
                $status = $allStatuses->get($statusKey);

                // Calculate check-in start time by subtracting check-in time from scheduled departure time
                // Always use scheduled departure_time (never new_departure_time) so check-in time only changes manually
                $departureTime = Carbon::createFromFormat('H:i:s', $stop->departure_time);
                $checkInStarts = $departureTime->copy()->subMinutes($checkInOffset)->format('H:i');

                // Calculate minutes until check-in starts
                $now = Carbon::now();
                $checkInStartTime = Carbon::createFromFormat('Y-m-d H:i', $now->format('Y-m-d').' '.$checkInStarts);

                // If check-in start time is in the past for today, check if departure is also in the past
                if ($checkInStartTime->isPast()) {
                    $departureTimeToday = Carbon::createFromFormat('Y-m-d H:i:s', $now->format('Y-m-d').' '.$stop->departure_time);
                    if ($departureTimeToday->isPast()) {
                        // Both check-in and departure are in the past, so this is for tomorrow
                        $checkInStartTime->addDay();
                        $minutesUntilCheckInStarts = (int) round($now->diffInMinutes($checkInStartTime, false));
                    } else {
                        // Check-in is in the past but departure is in the future, so check-in has already started
                        $minutesUntilCheckInStarts = 0;
                    }
                } else {
                    // Check-in is in the future for today
                    $minutesUntilCheckInStarts = (int) round($now->diffInMinutes($checkInStartTime, false));
                }

                // Ensure we don't return negative values
                if ($minutesUntilCheckInStarts < 0) {
                    $minutesUntilCheckInStarts = 0;
                }

                return [
                    'stop_id' => $stop->stop_id,
                    'stop_name' => $stop->stop_name,
                    'arrival_time' => substr($stop->arrival_time, 0, 5),
                    'departure_time' => substr($stop->departure_time, 0, 5),
                    'new_departure_time' => $stop->new_departure_time ? substr($stop->new_departure_time, 0, 5) : null,
                    'stop_sequence' => $stop->stop_sequence,
                    'status' => $statusKey,
                    'status_color' => $status?->color_rgb ?? '156,163,175',
                    'status_color_hex' => $this->rgbToHex($status?->color_rgb ?? '156,163,175'),
                    'departure_platform' => $this->getPlatformCode($stop->stop_id, $stop->platform_code, $stopStatus?->departure_platform, $uniqueTrip->trip_id),
                    'arrival_platform' => $this->getPlatformCode($stop->stop_id, $stop->platform_code, $stopStatus?->arrival_platform, $uniqueTrip->trip_id),
                    'is_realtime_update' => $stopStatus?->is_realtime_update ?? false,
                    'check_in_time' => $checkInOffset,
                    'check_in_starts' => $checkInStarts,
                    'minutes_until_check_in_starts' => $minutesUntilCheckInStarts,
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

            $firstStopStatusKey = $firstStopStatus?->status ?? 'on-time';
            $firstStopStatusObj = $allStatuses->get($firstStopStatusKey);

            // Get check-in status for this train
            $trainCheckInStatus = $allTrainCheckInStatuses->get($uniqueTrip->trip_id);
            $checkInStatus = $trainCheckInStatus?->checkInStatus;

            // Create the train entry matching API format
            $firstStopData = $mappedStops[0] ?? [];
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
                'status' => ucfirst($firstStopStatusKey),
                'status_color' => $firstStopStatusObj?->color_rgb ?? '156,163,175',
                'status_color_hex' => $this->rgbToHex($firstStopStatusObj?->color_rgb ?? '156,163,175'),
                'departure_platform' => $mappedStops[0]['departure_platform'] ?? 'TBD',
                'arrival_platform' => $mappedStops[count($mappedStops) - 1]['arrival_platform'] ?? 'TBD',
                'stop_name' => $stops->first()->stop_name,
                'is_realtime_update' => $firstStopStatus?->is_realtime_update ?? false,
                'check_in_time' => $checkInOffset,
                'check_in_starts' => $firstStopData['check_in_starts'] ?? null,
                'minutes_until_check_in_starts' => $firstStopData['minutes_until_check_in_starts'] ?? 0,
                'check_in_status' => $checkInStatus?->status ?? null,
                'check_in_status_id' => $checkInStatus?->id ?? null,
                'check_in_status_color' => $checkInStatus?->color_rgb ?? null,
                'check_in_status_color_hex' => $checkInStatus ? $this->rgbToHex($checkInStatus->color_rgb) : null,
                'stops' => $mappedStops,
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
        $this->loadTrains();
    }

    private function getPlatformCode($stopId, $platformCode, $manualPlatform, $tripId = null)
    {
        // If we have a platform code from the stop, use it
        if (! empty($platformCode)) {
            return $platformCode;
        }

        // If we have a manually set platform, use it
        if (! empty($manualPlatform) && $manualPlatform !== 'TBD') {
            return $manualPlatform;
        }

        // Check for platform assignments in the train_platform_assignments table
        if ($tripId) {
            $platformAssignment = DB::table('train_platform_assignments')
                ->where('trip_id', $tripId)
                ->where('stop_id', $stopId)
                ->first();

            if ($platformAssignment && ! empty($platformAssignment->platform_code) && $platformAssignment->platform_code !== 'TBD') {
                return $platformAssignment->platform_code;
            }
        }

        // If the stop ID is a base stop (like amsterdam_centraal), look up the actual platform from GTFS data
        if (strpos($stopId, '_') === false || ! preg_match('/_\d+[a-z]?$/', $stopId)) {
            // If the stop ID is a base stop (like amsterdam_centraal), look up available platform codes
            // from the platform-specific stops in the GTFS data
            $platformStops = DB::table('gtfs_stops')
                ->where('stop_id', 'LIKE', $stopId.'_%')
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
                DB::raw('MIN(gtfs_stop_times.departure_time) as departure'),
            ])
            ->groupBy('gtfs_stop_times.stop_sequence')
            ->orderBy('gtfs_stop_times.stop_sequence')
            ->get();

        $this->routeStops = $stops->map(function ($stop) {
            return [
                'name' => $stop->name,
                'sequence' => $stop->sequence,
                'arrival' => substr($stop->arrival, 0, 5),
                'departure' => substr($stop->departure, 0, 5),
            ];
        })->toArray();
    }

    private function updatePlatform($tripId, $stopId, $platform)
    {
        if (! $platform) {
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
                'updated_at' => now(),
            ]
        );

        // Update stop_statuses table
        StopStatus::updateOrCreate(
            [
                'trip_id' => $tripId,
                'stop_id' => $stopId,
            ],
            [
                'departure_platform' => $platform,
                'arrival_platform' => $platform,
                'updated_at' => now(),
            ]
        );
    }

    public function updateTrainCheckInStatus($tripId, $checkInStatusId = null): void
    {
        try {
            // Clear cache to ensure instant updates are visible
            $this->clearTrainGridCache();

            if ($checkInStatusId) {
                TrainCheckInStatus::updateOrCreate(
                    [
                        'trip_id' => $tripId,
                    ],
                    [
                        'check_in_status_id' => $checkInStatusId,
                    ]
                );
            } else {
                // If null, remove the check-in status
                TrainCheckInStatus::where('trip_id', $tripId)->delete();
            }

            // Reload the trains data
            $this->loadTrains();

            // Force a refresh of the view
            $this->dispatch('refresh');
        } catch (\Exception $e) {
            Log::error('Error updating train check-in status:', [
                'trip_id' => $tripId,
                'check_in_status_id' => $checkInStatusId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    public function updateTrainStatus($tripId, $status, $newTime = null, $platform = null, $checkInStartTime = null, $checkInStatusId = null)
    {
        try {
            // Clear cache to ensure instant updates are visible
            $this->clearTrainGridCache();

            // Get the current stop ID from the selected train
            $train = collect($this->trains)->first(function ($train) use ($tripId) {
                return $train['trip_id'] === $tripId;
            });

            if (! $train) {
                Log::error('Train not found in local state', ['trip_id' => $tripId]);

                return;
            }

            // Get the first stop from the stops array (this is the stop shown on the card)
            $firstStop = $train['stops'][0] ?? null;
            if (! $firstStop) {
                Log::error('No stops found for train', ['trip_id' => $tripId]);

                return;
            }

            // Use the exact stop_id from the card to ensure we update the correct stop
            $targetStopId = $firstStop['stop_id'];

            // Get the status object to get the color information
            $statusObj = Status::where('status', $status)->first();
            if (! $statusObj) {
                Log::error('Status not found', ['status' => $status]);

                return;
            }

            // Find the exact stop by stop_id first (the one shown on the card)
            $exactStop = DB::table('gtfs_stop_times')
                ->where('gtfs_stop_times.trip_id', $tripId)
                ->where('gtfs_stop_times.stop_id', $targetStopId)
                ->select('gtfs_stop_times.stop_id', 'gtfs_stop_times.new_departure_time', 'gtfs_stop_times.departure_time', 'gtfs_stop_times.stop_sequence')
                ->first();

            if ($exactStop) {
                // Found exact stop - also update all stops with the same sequence (API groups by sequence)
                // This ensures the API will pick up the updated stop regardless of which stop_id it selects
                $matchingStops = DB::table('gtfs_stop_times')
                    ->where('gtfs_stop_times.trip_id', $tripId)
                    ->where('gtfs_stop_times.stop_sequence', $exactStop->stop_sequence)
                    ->select('gtfs_stop_times.stop_id', 'gtfs_stop_times.new_departure_time', 'gtfs_stop_times.departure_time', 'gtfs_stop_times.stop_sequence')
                    ->get();
            } else {
                // If no exact match found, try to find by stop_sequence = 1 (first stop)
                Log::warning('No stops found by exact stop_id, trying stop_sequence = 1', [
                    'trip_id' => $tripId,
                    'target_stop_id' => $targetStopId,
                ]);

                $matchingStops = DB::table('gtfs_stop_times')
                    ->where('gtfs_stop_times.trip_id', $tripId)
                    ->where('gtfs_stop_times.stop_sequence', 1)
                    ->select('gtfs_stop_times.stop_id', 'gtfs_stop_times.new_departure_time', 'gtfs_stop_times.departure_time', 'gtfs_stop_times.stop_sequence')
                    ->get();
            }

            Log::info('Finding stops to update', [
                'trip_id' => $tripId,
                'target_stop_id' => $targetStopId,
                'found_stops' => $matchingStops->pluck('stop_id')->toArray(),
                'found_count' => $matchingStops->count(),
                'stop_sequence' => $exactStop->stop_sequence ?? ($matchingStops->first()->stop_sequence ?? null),
            ]);

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
                        'stop_id' => $matchingStop->stop_id,
                    ],
                    [
                        'status' => $status,
                        'status_color' => $statusObj->color_rgb,
                        'status_color_hex' => $this->rgbToHex($statusObj->color_rgb),
                        'updated_at' => now(),
                    ]
                );

                // Update the new departure time if it's provided
                if ($newTime && trim($newTime) !== '') {
                    // Format the time to HH:MM:SS format if it's in HH:MM format
                    $formattedTime = trim($newTime);
                    if (preg_match('/^\d{2}:\d{2}$/', $formattedTime)) {
                        $formattedTime = $formattedTime.':00';
                    }

                    // Validate the time format
                    if (! preg_match('/^\d{2}:\d{2}:\d{2}$/', $formattedTime)) {
                        Log::error('Invalid time format', [
                            'trip_id' => $tripId,
                            'stop_id' => $matchingStop->stop_id,
                            'provided_time' => $newTime,
                            'formatted_time' => $formattedTime,
                        ]);
                    } else {
                        // Always update when a time is provided (user explicitly set it)
                        $currentTime = $matchingStop->new_departure_time;
                        $updated = DB::table('gtfs_stop_times')
                            ->where('trip_id', $tripId)
                            ->where('stop_id', $matchingStop->stop_id)
                            ->update(['new_departure_time' => $formattedTime]);

                        Log::info('Updated departure time', [
                            'trip_id' => $tripId,
                            'stop_id' => $matchingStop->stop_id,
                            'old_time' => $currentTime,
                            'new_time' => $formattedTime,
                            'rows_updated' => $updated,
                        ]);
                    }
                }

                // Notify other components for each stop
                $this->dispatch('stop-status-updated', [
                    'trip_id' => $tripId,
                    'stop_id' => $matchingStop->stop_id,
                    'status' => $status,
                ]);
            }

            // Update check-in status if provided
            if ($checkInStatusId !== null) {
                $this->updateTrainCheckInStatus($tripId, $checkInStatusId);
            }

            // Update check-in time offset if check-in start time is provided
            if ($checkInStartTime !== null && trim($checkInStartTime) !== '') {
                // Get the train number and departure time from the database
                $trip = GtfsTrip::where('trip_id', $tripId)->first();

                if ($trip && $trip->trip_short_name) {
                    // Get the actual departure time (use new_departure_time if available, otherwise scheduled)
                    $firstStopTime = DB::table('gtfs_stop_times')
                        ->where('trip_id', $tripId)
                        ->where('stop_sequence', 1)
                        ->select('new_departure_time', 'departure_time')
                        ->first();

                    $actualDepartureTime = $firstStopTime->new_departure_time ?? $firstStopTime->departure_time;

                    if ($actualDepartureTime) {
                        // Calculate the offset in minutes from check-in start time and departure time
                        // Handle both HH:MM and HH:MM:SS formats
                        $checkInStartTimeFormatted = $checkInStartTime;
                        if (preg_match('/^\d{2}:\d{2}$/', $checkInStartTimeFormatted)) {
                            $checkInStartTimeFormatted = $checkInStartTimeFormatted.':00';
                        }

                        $checkInStart = Carbon::createFromFormat('H:i:s', $checkInStartTimeFormatted);
                        $departure = Carbon::createFromFormat('H:i:s', $actualDepartureTime);

                        // Calculate difference in minutes (departure - check-in start)
                        // diffInMinutes returns positive if departure is after check-in start
                        $checkInOffset = (int) round($checkInStart->diffInMinutes($departure, false));

                        // Only save if offset is positive (check-in must be before departure)
                        if ($checkInOffset > 0) {
                            $trainNumber = $trip->trip_short_name;

                            // Load existing specific train times
                            $specificTrainTimesSetting = Setting::where('key', 'specific_train_check_in_times')->value('value');
                            $specificTrainTimes = $specificTrainTimesSetting ? json_decode($specificTrainTimesSetting, true) : [];

                            // Update or add the train's check-in offset
                            $specificTrainTimes[$trainNumber] = $checkInOffset;

                            // Save back to settings
                            Setting::updateOrCreate(
                                ['key' => 'specific_train_check_in_times'],
                                ['value' => json_encode($specificTrainTimes)]
                            );

                            Log::info('Updated check-in time from start time', [
                                'trip_id' => $tripId,
                                'train_number' => $trainNumber,
                                'check_in_start_time' => $checkInStartTime,
                                'departure_time' => $actualDepartureTime,
                                'calculated_offset' => $checkInOffset,
                            ]);
                        } else {
                            Log::warning('Check-in start time must be before departure time', [
                                'trip_id' => $tripId,
                                'check_in_start_time' => $checkInStartTime,
                                'departure_time' => $actualDepartureTime,
                            ]);
                        }
                    }
                }
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
                'trace' => $e->getTraceAsString(),
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
            'status' => $status,
        ]);
    }

    /**
     * Clear the train grid cache for instant updates
     */
    private function clearTrainGridCache()
    {
        // Clear current 5-minute interval cache
        $interval = floor(now()->minute / 5) * 5;
        $currentCacheKey = "train_grid_group_{$this->group->id}_date_{$this->selectedDate}_{$interval}";
        Cache::forget($currentCacheKey);

        // Also clear the previous 5-minute interval cache in case we're at the boundary
        $previousInterval = floor(now()->subMinutes(5)->minute / 5) * 5;
        $previousCacheKey = "train_grid_group_{$this->group->id}_date_{$this->selectedDate}_{$previousInterval}";
        Cache::forget($previousCacheKey);

        // Clear API cache as well to ensure consistency
        $this->clearApiCache();
    }

    /**
     * Clear the API cache for train data
     */
    private function clearApiCache()
    {
        // The API uses per-minute cache keys, so we need to clear multiple minute intervals
        // Clear current minute and surrounding minutes to ensure we catch all variations
        for ($i = -2; $i <= 2; $i++) {
            $minute = now()->addMinutes($i);
            $cacheKey = 'train_api_today_'.$minute->format('Y-m-d_H:i');
            Cache::forget($cacheKey);
        }

        // Also clear 5-minute interval caches (if they exist)
        $interval = floor(now()->minute / 5) * 5;
        $currentApiCacheKey = 'train_api_today_'.now()->format('Y-m-d_H:').str_pad($interval, 2, '0', STR_PAD_LEFT);
        Cache::forget($currentApiCacheKey);

        $previousInterval = floor(now()->subMinutes(5)->minute / 5) * 5;
        $previousApiCacheKey = 'train_api_today_'.now()->subMinutes(5)->format('Y-m-d_H:').str_pad($previousInterval, 2, '0', STR_PAD_LEFT);
        Cache::forget($previousApiCacheKey);
    }

    public function render()
    {
        try {
            // Load statuses for the view (cached to avoid repeated queries)
            $statuses = Cache::remember('all_statuses', now()->addHours(24), function () {
                return Status::all();
            });

            // Load check-in statuses for the view (cached to avoid repeated queries)
            $checkInStatuses = Cache::remember('all_check_in_statuses', now()->addHours(24), function () {
                return CheckInStatus::orderByRaw('LOWER(status) ASC')->get();
            });

            return view('livewire.train-grid', [
                'statuses' => $statuses,
                'checkInStatuses' => $checkInStatuses,
            ]);
        } catch (\Exception $e) {
            Log::error('TrainGrid render error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'group_id' => $this->group->id ?? null,
            ]);

            // Return view with error state instead of crashing
            return view('livewire.train-grid', [
                'statuses' => collect(),
                'checkInStatuses' => collect(),
                'error' => 'Unable to load train data. Please refresh the page.',
            ]);
        }
    }
}
