<?php

namespace App\Livewire;

use App\Models\GtfsTrip;
use App\Models\GtfsRoute;
use App\Models\Group;
use Livewire\Component;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TrainInfoDisplay extends Component
{
    public Group $group;
    public $search = '';
    public $selectedDate = null;
    public $showDateFilter = false;
    public $trains = [];

    public function mount(Group $group)
    {
        $this->group = $group;
        $this->selectedDate = now()->format('Y-m-d');
        $this->loadTrains();
    }

    public function loadTrains()
    {
        if (!$this->group) {
            $this->trains = [];
            return;
        }

        $selectedRoutes = $this->group->selectedRoutes()
            ->where('is_active', true)
            ->pluck('route_id')
            ->toArray();

        if (empty($selectedRoutes)) {
            $this->trains = [];
            return;
        }

        $query = GtfsTrip::query()
            ->select([
                'gtfs_trips.trip_id',
                'gtfs_trips.service_id',
                'gtfs_trips.trip_short_name',
                'gtfs_trips.trip_headsign',
                'gtfs_trips.route_id',
                'gtfs_routes.route_long_name',
                'gtfs_routes.route_color',
                'gtfs_routes.route_text_color',
                'first_stop.departure_time',
                'last_stop.arrival_time'
            ])
            ->join('gtfs_routes', 'gtfs_trips.route_id', '=', 'gtfs_routes.route_id')
            ->join('gtfs_stop_times as first_stop', function ($join) {
                $join->on('gtfs_trips.trip_id', '=', 'first_stop.trip_id')
                    ->where('first_stop.stop_sequence', '=', 1);
            })
            ->join('gtfs_stop_times as last_stop', function ($join) {
                $join->on('gtfs_trips.trip_id', '=', 'last_stop.trip_id')
                    ->whereRaw('last_stop.stop_sequence = (
                        SELECT MAX(stop_sequence) 
                        FROM gtfs_stop_times 
                        WHERE trip_id = gtfs_trips.trip_id
                    )');
            })
            ->whereIn('gtfs_trips.route_id', $selectedRoutes);

        // Apply date filter if enabled
        if ($this->showDateFilter && $this->selectedDate) {
            $query->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('gtfs_calendar_dates')
                    ->whereColumn('gtfs_calendar_dates.service_id', 'gtfs_trips.service_id')
                    ->where('gtfs_calendar_dates.date', $this->selectedDate)
                    ->where('gtfs_calendar_dates.exception_type', 1);
            });
        }

        // Apply search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('gtfs_trips.trip_short_name', 'like', '%' . $this->search . '%')
                  ->orWhere('gtfs_trips.trip_headsign', 'like', '%' . $this->search . '%')
                  ->orWhere('gtfs_routes.route_long_name', 'like', '%' . $this->search . '%');
            });
        }

        $this->trains = $query->orderBy('gtfs_trips.trip_short_name')
            ->orderBy('first_stop.departure_time')
            ->limit(100)
            ->get()
            ->map(function ($trip) {
                return [
                    'trip_id' => $trip->trip_id,
                    'service_id' => $trip->service_id,
                    'train_number' => $trip->train_number,
                    'train_date' => $trip->formatted_date,
                    'human_readable_date' => $trip->human_readable_date,
                    'trip_short_name' => $trip->trip_short_name,
                    'trip_headsign' => $trip->trip_headsign,
                    'route_id' => $trip->route_id,
                    'route_long_name' => $trip->route_long_name,
                    'route_color' => $trip->route_color,
                    'route_text_color' => $trip->route_text_color,
                    'departure_time' => $trip->departure_time,
                    'arrival_time' => $trip->arrival_time,
                    'parsed_trip_id' => $this->parseTripId($trip->trip_id),
                    'parsed_service_id' => $this->parseServiceId($trip->service_id),
                ];
            });
    }

    public function parseTripId($tripId)
    {
        if (strpos($tripId, '-') !== false) {
            $parts = explode('-', $tripId);
            if (count($parts) >= 2) {
                $trainNumber = $parts[0];
                $datePart = $parts[1];
                
                if (strlen($datePart) === 4) {
                    $month = substr($datePart, 0, 2);
                    $day = substr($datePart, 2, 2);
                    return [
                        'train_number' => $trainNumber,
                        'date' => "{$month}-{$day}",
                        'month' => $month,
                        'day' => $day
                    ];
                }
            }
        }
        return null;
    }

    public function parseServiceId($serviceId)
    {
        if (strpos($serviceId, '-') !== false) {
            $parts = explode('-', $serviceId);
            if (count($parts) >= 2) {
                $trainNumber = $parts[0];
                $datePart = $parts[1];
                
                if (strlen($datePart) === 4) {
                    $month = substr($datePart, 0, 2);
                    $day = substr($datePart, 2, 2);
                    return [
                        'train_number' => $trainNumber,
                        'date' => "{$month}-{$day}",
                        'month' => $month,
                        'day' => $day
                    ];
                }
            }
        }
        return null;
    }

    public function updatedSearch()
    {
        $this->loadTrains();
    }

    public function updatedSelectedDate()
    {
        $this->loadTrains();
    }

    public function toggleDateFilter()
    {
        $this->showDateFilter = !$this->showDateFilter;
        $this->loadTrains();
    }

    public function render()
    {
        return view('livewire.train-info-display');
    }
} 