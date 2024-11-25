<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GtfsTrip;
use App\Models\TrainStatus;
use Carbon\Carbon;

class TrainController extends Controller
{
    public function today()
    {
        $today = Carbon::now()->format('Y-m-d');

        $trains = GtfsTrip::query()
            ->join('gtfs_calendar_dates', 'gtfs_trips.service_id', '=', 'gtfs_calendar_dates.service_id')
            ->join('gtfs_stop_times', 'gtfs_trips.trip_id', '=', 'gtfs_stop_times.trip_id')
            ->join('gtfs_routes', 'gtfs_trips.route_id', '=', 'gtfs_routes.route_id')
            ->leftJoin('train_statuses', 'gtfs_trips.trip_id', '=', 'train_statuses.trip_id')
            ->where('gtfs_trips.route_id', 'like', 'NLAMA%')
            ->whereDate('gtfs_calendar_dates.date', $today)
            ->where('gtfs_calendar_dates.exception_type', 1)
            ->where('gtfs_stop_times.stop_sequence', 1)
            ->select([
                'gtfs_trips.trip_headsign as number',
                'gtfs_trips.trip_id',
                'gtfs_trips.service_id',
                'gtfs_stop_times.departure_time as departure',
                'gtfs_routes.route_long_name',
                'gtfs_trips.trip_headsign as destination',
                'train_statuses.status'
            ])
            ->orderBy('gtfs_stop_times.departure_time')
            ->get()
            ->map(function ($train) {
                return [
                    'number' => $train->number,
                    'trip_id' => $train->trip_id,
                    'departure' => substr($train->departure, 0, 5),
                    'route_name' => $train->route_long_name,
                    'destination' => $train->destination,
                    'status' => $train->status ?? 'On-time',
                    'status_color' => ($train->status && $train->status !== 'on-time') ? 'red' : 'neutral'
                ];
            });

        return response()->json([
            'data' => $trains,
            'meta' => [
                'date' => $today,
                'count' => $trains->count()
            ]
        ]);
    }
}