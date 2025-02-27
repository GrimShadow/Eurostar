<?php

namespace App\Livewire;

use App\Models\GtfsTrip;
use App\Models\Setting;
use App\Models\SelectedRoute;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;

class TrainTable extends Component
{
    use WithPagination;

    public function getTrains()
    {
        $setting = Setting::where('key', 'train_table_routes')->first();
        $selectedRoutes = $setting ? $setting->value : [];
        $currentTime = Carbon::now()->format('H:i:s');

        return GtfsTrip::query()
            ->join('gtfs_stop_times', 'gtfs_trips.trip_id', '=', 'gtfs_stop_times.trip_id')
            ->join('gtfs_calendar_dates', 'gtfs_trips.service_id', '=', 'gtfs_calendar_dates.service_id')
            ->join('gtfs_routes', 'gtfs_trips.route_id', '=', 'gtfs_routes.route_id')
            ->leftJoin('train_statuses', 'gtfs_trips.trip_id', '=', 'train_statuses.trip_id')
            ->when(!empty($selectedRoutes), function ($query) use ($selectedRoutes) {
                $query->whereIn('gtfs_trips.route_id', $selectedRoutes);
            })
            ->whereDate('gtfs_calendar_dates.date', Carbon::now()->format('Y-m-d'))
            ->where('gtfs_calendar_dates.exception_type', 1)
            ->where('gtfs_stop_times.stop_sequence', 1)
            ->where('gtfs_stop_times.departure_time', '>=', $currentTime)
            ->select([
                'gtfs_trips.trip_short_name as number',
                'gtfs_stop_times.departure_time as departure',
                'gtfs_routes.route_long_name',
                'gtfs_routes.route_short_name',
                'train_statuses.status'
            ])
            ->orderBy('gtfs_stop_times.departure_time')
            ->paginate(8);
    }

    public function loadTrains()
    {
        $today = Carbon::now()->format('Y-m-d');
        $currentTime = Carbon::now()->format('H:i:s');

        // Cache the selected routes to avoid subquery
        $selectedRouteIds = SelectedRoute::where('is_active', true)
            ->pluck('route_id')
            ->toArray();

        $this->trains = GtfsTrip::query()
            ->select([
                'gtfs_trips.trip_short_name as number',
                'gtfs_stop_times.departure_time as departure',
                'gtfs_routes.route_long_name',
                'gtfs_routes.route_short_name',
                'train_statuses.status'
            ])
            ->join('gtfs_stop_times', function ($join) {
                $join->on('gtfs_trips.trip_id', '=', 'gtfs_stop_times.trip_id')
                    ->where('gtfs_stop_times.stop_sequence', 1);
            })
            ->join('gtfs_calendar_dates', function ($join) use ($today) {
                $join->on('gtfs_trips.service_id', '=', 'gtfs_calendar_dates.service_id')
                    ->whereDate('gtfs_calendar_dates.date', $today)
                    ->where('gtfs_calendar_dates.exception_type', 1);
            })
            ->join('gtfs_routes', 'gtfs_trips.route_id', '=', 'gtfs_routes.route_id')
            ->leftJoin('train_statuses', 'gtfs_trips.trip_id', '=', 'train_statuses.trip_id')
            ->whereIn('gtfs_trips.route_id', $selectedRouteIds)
            ->where('gtfs_stop_times.departure_time', '>=', $currentTime)
            ->orderBy('gtfs_stop_times.departure_time')
            ->limit(8)
            ->get()
            ->toArray();
    }

    public function render()
    {
        $paginator = $this->getTrains();
        
        // Transform the data within the paginator
        $paginator->getCollection()->transform(function ($trip) {
            $destinations = explode('-', $trip->route_long_name);
            $direction = substr($trip->route_short_name, 0, 2);
            $destination = $direction === 'NL' ? end($destinations) : reset($destinations);

            return [
                'number' => $trip->number,
                'departure' => substr($trip->departure, 0, 5),
                'destination' => trim($destination),
                'status' => $trip->status ?? 'On-time',
                'platform' => '-'
            ];
        });

        return view('livewire.train-table', [
            'trains' => $paginator
        ]);
    }
} 