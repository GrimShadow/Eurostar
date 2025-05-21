<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GtfsTrip;
use App\Models\TrainStatus;
use App\Models\StopStatus;
use App\Models\Setting;
use Carbon\Carbon;
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

    public function today()
    {
        $today = Carbon::now()->format('Y-m-d');
        $startTime = '00:00:00';
        $endTime = '23:59:59';

        // Get the global check-in offset from settings
        $globalCheckInOffset = Setting::where('key', 'global_check_in_offset')->value('value') ?? 90;

        $selectedRoutes = DB::table('selected_routes')
            ->where('is_active', true)
            ->pluck('route_id')
            ->toArray();

        if (empty($selectedRoutes)) {
            return response()->json([
                'data' => [],
                'meta' => [
                    'date' => $today,
                    'count' => 0,
                    'time_window' => [
                        'start' => $startTime,
                        'end' => $endTime
                    ]
                ]
            ]);
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
            ->where('departure_stop.departure_time', '>=', $startTime)
            ->where('departure_stop.departure_time', '<=', $endTime)
            ->orderBy('departure_stop.departure_time')
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
                'gtfs_stops.platform_code'
            ])
            ->orderBy('gtfs_stop_times.trip_id')
            ->orderBy('gtfs_stop_times.stop_sequence')
            ->get()
            ->groupBy('trip_id')
            ->map(function ($tripStops) {
                return $tripStops->groupBy(function ($stop) {
                    return $stop->stop_sequence . '_' . $stop->arrival_time . '_' . $stop->departure_time;
                })->map(function ($groupedStops) {
                    // Take the first stop that has a platform code, or the first one if none have it
                    return $groupedStops->first(function ($stop) {
                        return !empty($stop->platform_code);
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

        // Map the trips with their stops
        $trains = $trips->unique('trip_id')->map(function ($train) use ($stops, $stopStatuses, $globalCheckInOffset) {
            $trainStops = $stops->get($train->trip_id, collect())->map(function ($stop) use ($stopStatuses, $train, $globalCheckInOffset) {
                $stopStatus = $stopStatuses->get($train->trip_id)?->get($stop->stop_id);
                
                return [
                    'stop_id' => $stop->stop_id,
                    'stop_name' => $stop->stop_name,
                    'arrival_time' => substr($stop->arrival_time, 0, 5),
                    'departure_time' => substr($stop->departure_time, 0, 5),
                    'new_departure_time' => $stop->new_departure_time ? substr($stop->new_departure_time, 0, 5) : null,
                    'stop_sequence' => $stop->stop_sequence,
                    'status' => $stopStatus ? $stopStatus->status : 'on-time',
                    'status_color' => $stopStatus ? $stopStatus->status_color : '156,163,175',
                    'status_color_hex' => $stopStatus ? $stopStatus->status_color_hex : '#9CA3AF',
                    'departure_platform' => $stop->platform_code ?? ($stopStatus ? $stopStatus->departure_platform : 'TBD'),
                    'arrival_platform' => $stop->platform_code ?? ($stopStatus ? $stopStatus->arrival_platform : 'TBD'),
                    'check_in_time' => $globalCheckInOffset
                ];
            })->values();

            return [
                'number' => $train->number,
                'trip_id' => $train->trip_id,
                'departure' => substr($train->departure_time, 0, 5),
                'route_name' => $train->route_long_name,
                'train_id' => $train->trip_headsign,
                'status' => ucfirst($train->status_text ?? $train->train_status ?? 'On time'),
                'status_color' => $train->status_color ?? '156,163,175',
                'status_color_hex' => $this->rgbToHex($train->status_color ?? '156,163,175'),
                'departure_platform' => $train->departure_platform ?? 'TBD',
                'arrival_platform' => $train->arrival_platform ?? 'TBD',
                'stops' => $trainStops
            ];
        });

        return response()->json([
            'data' => [
                'stops' => $trains
            ],
            'meta' => [
                'date' => $today,
                'count' => $trains->count(),
                'time_window' => [
                    'start' => $startTime,
                    'end' => $endTime
                ]
            ]
        ]);
    }
}