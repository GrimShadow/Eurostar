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
        $currentTime = Carbon::now()->format('H:i:s');
        $endTime = Carbon::now()->addHours(4)->format('H:i:s');

        $selectedRoutes = DB::table('train_table_routes')
            ->where('is_active', true)
            ->pluck('route_id')
            ->toArray();

        if (empty($selectedRoutes)) {
            return response()->json([
                'data' => [],
                'meta' => [
                    'date' => $today,
                    'count' => 0
                ]
            ]);
        }

        // First, get unique trips with their first stop times
        $uniqueTrips = DB::table('gtfs_trips')
            ->select([
                DB::raw('DISTINCT gtfs_trips.trip_short_name'),
                DB::raw('MIN(gtfs_trips.trip_id) as trip_id'),
                'gtfs_trips.route_id',
                DB::raw('MIN(first_stop.departure_time) as departure_time')
            ])
            ->join('gtfs_stop_times as first_stop', function ($join) {
                $join->on('gtfs_trips.trip_id', '=', 'first_stop.trip_id')
                    ->where('first_stop.stop_sequence', '=', 1);
            })
            ->whereIn('gtfs_trips.route_id', $selectedRoutes)
            ->where('first_stop.departure_time', '>=', $currentTime)
            ->where('first_stop.departure_time', '<=', $endTime)
            ->groupBy('gtfs_trips.trip_short_name', 'gtfs_trips.route_id')
            ->orderBy('departure_time');

        // Then join with other tables to get the full information
        $query = DB::table(DB::raw("({$uniqueTrips->toSql()}) as unique_trips"))
            ->mergeBindings($uniqueTrips)
            ->select([
                'unique_trips.trip_short_name as number',
                'unique_trips.trip_id',
                'unique_trips.route_id',
                'unique_trips.departure_time as departure',
                'last_stop.arrival_time as arrival',
                'gtfs_routes.route_long_name',
                'gtfs_trips.trip_headsign as destination',
                'train_statuses.status as train_status',
                'statuses.status as status_text',
                'statuses.color_rgb',
                'departure_platform.platform_code as departure_platform',
                'arrival_platform.platform_code as arrival_platform'
            ])
            ->join('gtfs_trips', 'unique_trips.trip_id', '=', 'gtfs_trips.trip_id')
            ->join('gtfs_stop_times as last_stop', function ($join) {
                $join->on('gtfs_trips.trip_id', '=', 'last_stop.trip_id')
                    ->whereRaw('last_stop.stop_sequence = (
                        SELECT MAX(stop_sequence) 
                        FROM gtfs_stop_times 
                        WHERE trip_id = gtfs_trips.trip_id
                    )');
            })
            ->join('gtfs_routes', 'gtfs_trips.route_id', '=', 'gtfs_routes.route_id')
            ->leftJoin('train_platform_assignments as departure_platform', function ($join) {
                $join->on('gtfs_trips.trip_id', '=', 'departure_platform.trip_id')
                    ->whereRaw('departure_platform.stop_id = (
                        SELECT stop_id 
                        FROM gtfs_stop_times 
                        WHERE trip_id = gtfs_trips.trip_id 
                        AND stop_sequence = 1
                    )');
            })
            ->leftJoin('train_platform_assignments as arrival_platform', function ($join) {
                $join->on('gtfs_trips.trip_id', '=', 'arrival_platform.trip_id')
                    ->whereRaw('arrival_platform.stop_id = (
                        SELECT stop_id 
                        FROM gtfs_stop_times 
                        WHERE trip_id = gtfs_trips.trip_id 
                        AND stop_sequence = (
                            SELECT MAX(stop_sequence) 
                            FROM gtfs_stop_times 
                            WHERE trip_id = gtfs_trips.trip_id
                        )
                    )');
            })
            ->leftJoin('train_statuses', 'gtfs_trips.trip_id', '=', 'train_statuses.trip_id')
            ->leftJoin('statuses', 'train_statuses.status', '=', 'statuses.status')
            ->orderBy('unique_trips.departure_time');

        $trains = $query->get()->map(function ($train) {
            return [
                'number' => $train->number,
                'trip_id' => $train->trip_id,
                'departure' => substr($train->departure, 0, 5),
                'route_name' => $train->route_long_name,
                'destination' => $train->destination,
                'status' => ucfirst($train->status_text ?? $train->train_status ?? 'on-time'),
                'status_color' => $train->color_rgb ?? '156,163,175',
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
                    'start' => $currentTime,
                    'end' => $endTime
                ]
            ]
        ]);
    }
}