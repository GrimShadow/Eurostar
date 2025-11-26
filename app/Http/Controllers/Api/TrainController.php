<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GtfsTrip;
use App\Models\Setting;
use App\Models\Status;
use App\Models\StopStatus;
use App\Models\TrainCheckInStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TrainController extends Controller
{
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

    public function today()
    {
        // Create a cache key based on current time (rounded to nearest minute for more frequent updates)
        $cacheKey = 'train_api_today_'.now()->format('Y-m-d_H:i');

        // Cache the expensive query result for 1 minute to allow minutes to update more frequently
        $result = Cache::remember($cacheKey, now()->addMinute(), function () {
            return $this->getTrainsApiData();
        });

        return response()->json($result)
            ->header('Cache-Control', 'public, max-age=60'); // 1 minute client-side cache
    }

    private function getTrainsApiData()
    {
        // Ensure we're reading the most up-to-date data
        DB::connection()->reconnect();

        $today = Carbon::now()->format('Y-m-d');
        $startTime = '00:00:00';
        $endTime = '23:59:59';

        // Get the global check-in offset from settings
        $globalCheckInOffset = Setting::where('key', 'global_check_in_offset')->value('value') ?? 90;

        // Get specific train check-in times
        $specificTrainTimes = Setting::where('key', 'specific_train_check_in_times')->value('value');
        $specificTrainTimes = $specificTrainTimes ? json_decode($specificTrainTimes, true) : [];

        $selectedRoutes = DB::table('selected_routes')
            ->where('is_active', true)
            ->pluck('route_id')
            ->toArray();

        if (empty($selectedRoutes)) {
            return [
                'data' => [],
                'meta' => [
                    'date' => $today,
                    'count' => 0,
                    'time_window' => [
                        'start' => $startTime,
                        'end' => $endTime,
                    ],
                ],
            ];
        }

        // First, get all the trips with their basic information
        $trips = GtfsTrip::query()
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
            ->whereIn('gtfs_trips.route_id', $selectedRoutes)
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('gtfs_calendar_dates')
                    ->whereColumn('gtfs_calendar_dates.service_id', 'gtfs_trips.service_id')
                    ->where('gtfs_calendar_dates.date', now()->format('Y-m-d'))
                    ->where('gtfs_calendar_dates.exception_type', 1);
            })
            ->where('departure_stop.departure_time', '>=', $startTime)
            ->where('departure_stop.departure_time', '<=', $endTime)
            ->orderBy('departure_stop.departure_time')
            ->limit(1000) // Add limit to prevent excessive data loading
            ->get();

        // Get all stops for each trip
        $tripIds = $trips->pluck('trip_id');
        $stops = DB::table('gtfs_stop_times')
            ->join('gtfs_stops', 'gtfs_stop_times.stop_id', '=', 'gtfs_stops.stop_id')
            ->whereIn('gtfs_stop_times.trip_id', $tripIds)
            ->select([
                'gtfs_stop_times.trip_id',
                'gtfs_stop_times.stop_id',
                'gtfs_stop_times.stop_sequence',
                'gtfs_stop_times.arrival_time',
                'gtfs_stop_times.departure_time',
                'gtfs_stop_times.new_departure_time',
                'gtfs_stops.stop_name',
                'gtfs_stops.platform_code',
            ])
            ->orderBy('gtfs_stop_times.trip_id')
            ->orderBy('gtfs_stop_times.stop_sequence')
            ->get()
            ->groupBy('trip_id')
            ->map(function ($tripStops) {
                return $tripStops->groupBy(function ($stop) {
                    return $stop->stop_sequence.'_'.$stop->arrival_time.'_'.$stop->departure_time;
                })->map(function ($groupedStops) {
                    // Prioritize stops with new_departure_time set (updated stops)
                    $stopWithNewTime = $groupedStops->first(function ($stop) {
                        return ! empty($stop->new_departure_time);
                    });

                    if ($stopWithNewTime) {
                        return $stopWithNewTime;
                    }

                    // Otherwise, take the first stop that has a platform code, or the first one if none have it
                    return $groupedStops->first(function ($stop) {
                        return ! empty($stop->platform_code);
                    }) ?? $groupedStops->first();
                })->values();
            });

        // Get all stop statuses
        $stopStatuses = StopStatus::whereIn('trip_id', $tripIds)
            ->get()
            ->groupBy('trip_id')
            ->map(function ($statuses) {
                return $statuses->keyBy('stop_id');
            });

        // Get all train check-in statuses
        $trainCheckInStatuses = TrainCheckInStatus::whereIn('trip_id', $tripIds)
            ->with('checkInStatus')
            ->get()
            ->keyBy('trip_id');

        // Map the trips with their stops
        $trains = $trips->unique('trip_id')->map(function ($train) use ($stops, $stopStatuses, $trainCheckInStatuses, $globalCheckInOffset, $specificTrainTimes) {
            // Get check-in status for this train
            $trainCheckInStatus = $trainCheckInStatuses->get($train->trip_id);
            $checkInStatus = $trainCheckInStatus?->checkInStatus;

            $trainStops = $stops->get($train->trip_id, collect())->map(function ($stop) use ($stopStatuses, $train, $checkInStatus, $globalCheckInOffset, $specificTrainTimes) {
                $stopStatus = $stopStatuses->get($train->trip_id)?->get($stop->stop_id);

                // Determine check-in offset for this train
                $trainNumber = $train->number;
                $checkInOffset = (int) ($specificTrainTimes[$trainNumber] ?? $globalCheckInOffset);

                // Calculate check-in start time by subtracting check-in time from departure time
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

                // Calculate minutes difference if there's a new departure time
                $newDepartMin = null;
                if ($stop->new_departure_time) {
                    $newDepartureTime = Carbon::createFromFormat('H:i:s', $stop->new_departure_time);
                    $newDepartMin = $departureTime->diffInMinutes($newDepartureTime, false);
                }

                // Format the status updated timestamp in local timezone
                $statusUpdatedAt = null;
                if ($stopStatus?->updated_at) {
                    $statusUpdatedAt = Carbon::parse($stopStatus->updated_at)
                        ->setTimezone('Europe/Amsterdam')
                        ->format('Y-m-d H:i:s');
                }

                return [
                    'stop_id' => $stop->stop_id,
                    'stop_name' => $stop->stop_name,
                    'arrival_time' => substr($stop->arrival_time, 0, 5),
                    'departure_time' => substr($stop->departure_time, 0, 5),
                    'new_departure_time' => $stop->new_departure_time ? substr($stop->new_departure_time, 0, 5) : null,
                    'new_depart_min' => $newDepartMin,
                    'stop_sequence' => $stop->stop_sequence,
                    'status' => $stopStatus?->status ?? 'on-time',
                    'status_color' => $stopStatus?->status_color ?? '156,163,175',
                    'status_color_hex' => $stopStatus?->status_color_hex ?? '#9CA3AF',
                    'status_updated_at' => $statusUpdatedAt,
                    'check_in_status' => $checkInStatus?->status ?? null,
                    'check_in_status_color' => $checkInStatus?->color_rgb ?? null,
                    'check_in_status_color_hex' => $checkInStatus ? $this->rgbToHex($checkInStatus->color_rgb) : null,
                    'departure_platform' => $this->getPlatformCode($stop->stop_id, $stop->platform_code, $stopStatus?->departure_platform, $train->trip_id),
                    'arrival_platform' => $this->getPlatformCode($stop->stop_id, $stop->platform_code, $stopStatus?->arrival_platform, $train->trip_id),
                    'check_in_time' => (int) $checkInOffset,
                    'check_in_starts' => $checkInStarts,
                    'minutes_until_check_in_starts' => $minutesUntilCheckInStarts,
                ];
            })->values();

            // Get the first stop
            $firstStop = $trainStops->first();
            $firstStopStatus = null;

            // If the first stop is amsterdam_centraal_15, check if we have a status for amsterdam_centraal
            if (str_ends_with($firstStop['stop_id'], '_15')) {
                $baseStopId = str_replace('_15', '', $firstStop['stop_id']);
                $baseStopStatus = $stopStatuses->get($train->trip_id)?->get($baseStopId);
                $firstStopStatus = $baseStopStatus ?? $stopStatuses->get($train->trip_id)?->get($firstStop['stop_id']);
            } else {
                $firstStopStatus = $stopStatuses->get($train->trip_id)?->get($firstStop['stop_id']);
            }

            return [
                'number' => $train->number,
                'trip_id' => $train->trip_id,
                'departure' => substr($train->departure_time, 0, 5),
                'route_name' => $train->route_long_name,
                'train_id' => $train->trip_headsign,
                'status' => ucfirst($firstStopStatus?->status ?? 'on-time'),
                'status_color' => $firstStopStatus?->status_color ?? '156,163,175',
                'status_color_hex' => $firstStopStatus?->status_color_hex ?? '#9CA3AF',
                'departure_platform' => $firstStop['departure_platform'] ?? 'TBD',
                'arrival_platform' => $trainStops->last()['arrival_platform'] ?? 'TBD',
                'stops' => $trainStops,
            ];
        })->values();

        return [
            'data' => [
                'stops' => $trains,
            ],
            'meta' => [
                'date' => $today,
                'count' => $trains->count(),
                'time_window' => [
                    'start' => $startTime,
                    'end' => $endTime,
                ],
            ],
        ];
    }
}
