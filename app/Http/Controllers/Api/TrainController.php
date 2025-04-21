<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GtfsTrip;
use App\Models\TrainStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TrainController extends Controller
{
    public function today()
    {
        $today = Carbon::now()->format('Y-m-d');
        $startTime = '00:00:00';
        $endTime = '23:59:59';

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
            ->where('departure_stop.departure_time', '>=', $startTime)
            ->where('departure_stop.departure_time', '<=', $endTime)
            ->orderBy('departure_stop.departure_time')
            ->get()
            ->map(function ($train) {
                return [
                    'number' => $train->number,
                    'trip_id' => $train->trip_id,
                    'departure' => substr($train->departure_time, 0, 5),
                    'route_name' => $train->route_long_name,
                    'destination' => $train->trip_headsign,
                    'status' => ucfirst($train->status_text ?? $train->train_status ?? 'on-time'),
                    'status_color' => $train->status_color ?? '156,163,175',
                    'departure_platform' => $train->departure_platform ?? 'TBD',
                    'arrival_platform' => $train->arrival_platform ?? 'TBD'
                ];
            });

        return response()->json([
            'data' => $trains,
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