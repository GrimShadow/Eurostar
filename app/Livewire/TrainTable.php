<?php

namespace App\Livewire;

use App\Models\GtfsTrip;
use App\Models\Setting;
use App\Models\SelectedRoute;
use Livewire\Component;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class TrainTable extends Component
{
    public $trains = [];
    public $selectedRoutes = [];
    public $date;
    public $time;
    public $page = 1;
    public $perPage = 8;
    public $total = 0;
    public $group;

    protected $listeners = [
        'echo:train-statuses,TrainStatusUpdated' => 'handleTrainStatusUpdated'
    ];

    public function handleTrainStatusUpdated($event)
    {
        $this->loadTrains();
    }

    public function mount($group)
    {
        $this->group = $group;
        $this->date = Carbon::now()->format('Y-m-d');
        $this->time = Carbon::now()->format('H:i');
        $this->loadSelectedRoutes();
        $this->loadTrains();
    }

    public function loadSelectedRoutes()
    {
        if (!$this->group) {
            Log::info('TrainTable - No group provided');
            $this->selectedRoutes = [];
            return;
        }

        // Get both API routes and group-specific routes
        $apiRoutes = DB::table('selected_routes')
            ->where('is_active', true)
            ->pluck('route_id')
            ->toArray();

        $groupRoutes = $this->group->trainTableSelections()
            ->where('is_active', true)
            ->pluck('route_id')
            ->toArray();

        // Combine both sets of routes
        $this->selectedRoutes = array_unique(array_merge($apiRoutes, $groupRoutes));
    }

    public function loadTrains()
    {
        $query = $this->getTrainsQuery();
        $this->total = $query->count();
        
        $trains = $query->skip(($this->page - 1) * $this->perPage)
            ->take($this->perPage)
            ->get();

        $this->trains = $trains->map(function ($train) {
            return [
                'number' => $train->number,
                'departure' => substr($train->departure, 0, 5),
                'route_long_name' => $train->route_long_name,
                'status' => ucfirst($train->status_text ?? $train->train_status ?? 'on-time'),
                'status_color' => $train->color_rgb ?? '156,163,175',
                'departure_platform' => $train->departure_platform ?? 'TBD',
                'arrival_platform' => $train->arrival_platform ?? 'TBD'
            ];
        });

        // Debug the first train's data
        if ($trains->isNotEmpty()) {
            $firstTrain = $trains->first();
        }
    }

    public function getTrainsQuery()
    {
        $today = Carbon::now()->format('Y-m-d');
        $currentTime = Carbon::now()->format('H:i:s');
        $endTime = Carbon::now()->addHours(4)->format('H:i:s');

        if (!$this->group) {
            Log::info('TrainTable - No group provided');
            return GtfsTrip::query()->whereRaw('1 = 0');
        }

        $selectedRoutes = $this->group->trainTableSelections()
            ->where('is_active', true)
            ->pluck('route_id')
            ->toArray();

        if (empty($selectedRoutes)) {
            Log::info('TrainTable - No routes selected, returning empty query');
            return GtfsTrip::query()->whereRaw('1 = 0');
        }

        // Get unique trips with their first and last stops
        $query = DB::table('gtfs_trips')
            ->select([
                DB::raw('DISTINCT gtfs_trips.trip_short_name as number'),
                'gtfs_trips.trip_id',
                'gtfs_trips.route_id',
                'first_stop.departure_time as departure',
                'last_stop.arrival_time as arrival',
                'gtfs_routes.route_long_name',
                'gtfs_trips.trip_headsign as destination',
                'train_statuses.status as train_status',
                'statuses.status as status_text',
                'statuses.color_rgb',
                'departure_platform.platform_code as departure_platform',
                'arrival_platform.platform_code as arrival_platform'
            ])
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
            ->join('gtfs_routes', 'gtfs_trips.route_id', '=', 'gtfs_routes.route_id')
            ->leftJoin('train_platform_assignments as departure_platform', function ($join) {
                $join->on('gtfs_trips.trip_id', '=', 'departure_platform.trip_id')
                    ->whereRaw('departure_platform.stop_id = first_stop.stop_id');
            })
            ->leftJoin('train_platform_assignments as arrival_platform', function ($join) {
                $join->on('gtfs_trips.trip_id', '=', 'arrival_platform.trip_id')
                    ->whereRaw('arrival_platform.stop_id = last_stop.stop_id');
            })
            ->leftJoin('train_statuses', 'gtfs_trips.trip_id', '=', 'train_statuses.trip_id')
            ->leftJoin('statuses', 'train_statuses.status', '=', 'statuses.status')
            ->whereIn('gtfs_trips.route_id', $selectedRoutes)
            ->where('first_stop.departure_time', '>=', $currentTime)
            ->where('first_stop.departure_time', '<=', $endTime)
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('gtfs_calendar_dates')
                    ->whereColumn('gtfs_calendar_dates.service_id', 'gtfs_trips.service_id')
                    ->where('gtfs_calendar_dates.date', now()->format('Y-m-d'))
                    ->where('gtfs_calendar_dates.exception_type', 1);
            })
            ->groupBy('gtfs_trips.trip_short_name', 'gtfs_trips.trip_id', 'gtfs_trips.route_id', 
                     'first_stop.departure_time', 'last_stop.arrival_time', 'gtfs_routes.route_long_name',
                     'gtfs_trips.trip_headsign', 'train_statuses.status', 'statuses.status',
                     'statuses.color_rgb', 'departure_platform.platform_code', 'arrival_platform.platform_code')
            ->orderBy('first_stop.departure_time');

        return $query;
    }

    public function nextPage()
    {
        if ($this->page * $this->perPage < $this->total) {
            $this->page++;
            $this->loadTrains();
        }
    }

    public function previousPage()
    {
        if ($this->page > 1) {
            $this->page--;
            $this->loadTrains();
        }
    }

    public function toggleTableRoute($routeId)
    {
        
        $route = DB::table('selected_routes')
            ->where('route_id', $routeId)
            ->first();

        if ($route) {
            $newStatus = !$route->is_active;
            DB::table('selected_routes')
                ->where('route_id', $routeId)
                ->update(['is_active' => $newStatus]);
        } else {
            DB::table('selected_routes')
                ->insert([
                    'route_id' => $routeId,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
        }

        $this->loadSelectedRoutes();
        $this->loadTrains();
    }

    public function render()
    {
        return view('livewire.train-table', [
            'trains' => $this->trains,
            'total' => $this->total,
            'page' => $this->page,
            'perPage' => $this->perPage
        ]);
    }
} 