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

class TrainTable extends Component
{
    public $trains = [];
    public $selectedRoutes = [];
    public $date;
    public $time;
    public $page = 1;
    public $perPage = 8;
    public $total = 0;

    protected $listeners = [
        'echo:train-statuses,TrainStatusUpdated' => 'handleTrainStatusUpdated'
    ];

    public function handleTrainStatusUpdated($event)
    {
        $this->loadTrains();
    }

    public function mount()
    {
        $this->date = Carbon::now()->format('Y-m-d');
        $this->time = Carbon::now()->format('H:i');
        $this->loadSelectedRoutes();
        $this->loadTrains();
    }

    public function loadSelectedRoutes()
    {
        $this->selectedRoutes = DB::table('train_table_routes')
            ->where('is_active', true)
            ->pluck('route_id')
            ->toArray();
        
        Log::info('TrainTable - Loaded Selected Routes', ['routes' => $this->selectedRoutes]);
    }

    public function loadTrains()
    {
        $query = $this->getTrainsQuery();
        $this->total = $query->count();
        
        Log::info('TrainTable - Total Trains Found', ['count' => $this->total]);

        $trains = $query->skip(($this->page - 1) * $this->perPage)
            ->take($this->perPage)
            ->get();

        Log::info('TrainTable - Trains Found', [
            'count' => $trains->count(),
            'routes' => $trains->pluck('route_id')->unique()->values()->toArray()
        ]);

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
            Log::info('TrainTable - First Train Data', [
                'number' => $firstTrain->number,
                'route_id' => $firstTrain->route_id,
                'departure' => $firstTrain->departure,
                'departure_platform' => $firstTrain->departure_platform,
                'arrival_platform' => $firstTrain->arrival_platform,
                'route_long_name' => $firstTrain->route_long_name,
                'status' => $firstTrain->status_text ?? $firstTrain->train_status ?? 'on-time'
            ]);
        }
    }

    public function getTrainsQuery()
    {
        $today = Carbon::now()->format('Y-m-d');
        $currentTime = Carbon::now()->format('H:i:s');
        $endTime = Carbon::now()->addHours(4)->format('H:i:s');

        $selectedRoutes = DB::table('train_table_routes')
            ->where('is_active', true)
            ->pluck('route_id')
            ->toArray();

        Log::info('TrainTable - Debug Info', [
            'selected_routes' => $selectedRoutes,
            'current_time' => $currentTime,
            'end_time' => $endTime,
            'total_routes' => DB::table('gtfs_routes')->count(),
            'total_trips' => DB::table('gtfs_trips')->count(),
            'total_stop_times' => DB::table('gtfs_stop_times')->count()
        ]);

        if (empty($selectedRoutes)) {
            Log::info('TrainTable - No routes selected, returning empty query');
            return GtfsTrip::query()->whereRaw('1 = 0');
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

        // Log the intermediate query results
        $uniqueTripsCount = DB::table(DB::raw("({$uniqueTrips->toSql()}) as temp"))
            ->mergeBindings($uniqueTrips)
            ->count();
            
        Log::info('TrainTable - Unique Trips Count', ['count' => $uniqueTripsCount]);

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

        // Log the SQL query for debugging
        Log::info('TrainTable - Query', [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings()
        ]);

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
        Log::info('TrainTable - Toggling Route', ['route_id' => $routeId]);
        
        $route = DB::table('train_table_routes')
            ->where('route_id', $routeId)
            ->first();

        if ($route) {
            $newStatus = !$route->is_active;
            DB::table('train_table_routes')
                ->where('route_id', $routeId)
                ->update(['is_active' => $newStatus]);
            Log::info('TrainTable - Updated Route Status', ['route_id' => $routeId, 'new_status' => $newStatus]);
        } else {
            DB::table('train_table_routes')
                ->insert([
                    'route_id' => $routeId,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            Log::info('TrainTable - Added New Route', ['route_id' => $routeId]);
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