<?php

namespace App\Livewire;

use App\Models\GtfsTrip;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

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
        'echo:train-statuses,TrainStatusUpdated' => 'handleTrainStatusUpdated',
    ];

    public function handleTrainStatusUpdated($event)
    {
        // Clear cache when train status is updated to ensure instant updates
        $this->clearTrainTableCache();
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
        if (! $this->group) {
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
        // Use 5-minute cache intervals for better cache hit rate
        $interval = floor(now()->minute / 5) * 5;
        $cacheKey = "train_table_group_{$this->group->id}_page_{$this->page}_".now()->format('Y-m-d')."_{$interval}";

        // Cache the expensive query result for 5 minutes
        $results = Cache::remember($cacheKey, now()->addMinutes(5), function () {
            return $this->getTrainsData();
        });

        $this->trains = $results['trains'];
        $this->total = $results['total'];
    }

    private function getTrainsData()
    {
        $query = $this->getTrainsQuery();

        // Optimize count query separately to avoid GROUP BY overhead
        $countQuery = $this->getTrainsCountQuery();
        $total = $countQuery->count();

        // Get paginated results with proper limits
        $trains = $query->skip(($this->page - 1) * $this->perPage)
            ->take($this->perPage)
            ->get();

        $processedTrains = $trains->map(function ($train) {
            // Use realtime status if available, otherwise fall back to manual status
            $status = $train->realtime_status ?? $train->status_text ?? $train->train_status ?? 'on-time';

            // Use realtime status color if available, otherwise fall back to manual status color
            $statusColor = $train->realtime_status_color ?? $train->color_rgb ?? '156,163,175';

            return [
                'number' => $train->number,
                'departure' => substr($train->departure, 0, 5),
                'route_long_name' => $train->route_long_name,
                'status' => ucfirst($status),
                'status_color' => $statusColor,
                'departure_platform' => $train->departure_platform ?? 'TBD',
                'arrival_platform' => $train->arrival_platform ?? 'TBD',
                'is_realtime_update' => ! empty($train->realtime_status),
            ];
        });

        return [
            'trains' => $processedTrains,
            'total' => $total,
        ];
    }

    private function getTrainsCountQuery()
    {
        $today = Carbon::now()->format('Y-m-d');
        $currentTime = Carbon::now()->format('H:i:s');
        $endTime = Carbon::now()->addHours(4)->format('H:i:s');

        if (! $this->group) {
            return GtfsTrip::query()->whereRaw('1 = 0');
        }

        $selectedRoutes = $this->group->trainTableSelections()
            ->where('is_active', true)
            ->pluck('route_id')
            ->toArray();

        if (empty($selectedRoutes)) {
            return GtfsTrip::query()->whereRaw('1 = 0');
        }

        // Simplified count query without complex joins
        return DB::table('gtfs_trips')
            ->join('gtfs_stop_times as first_stop', function ($join) {
                $join->on('gtfs_trips.trip_id', '=', 'first_stop.trip_id')
                    ->where('first_stop.stop_sequence', '=', 1);
            })
            ->whereIn('gtfs_trips.route_id', $selectedRoutes)
            ->where(function ($query) use ($currentTime, $endTime) {
                $query->where(function ($q) use ($currentTime, $endTime) {
                    $q->where('first_stop.departure_time', '>=', $currentTime)
                        ->where('first_stop.departure_time', '<=', $endTime);
                })->orWhere(function ($q) use ($currentTime, $endTime) {
                    $q->where('first_stop.new_departure_time', '>=', $currentTime)
                        ->where('first_stop.new_departure_time', '<=', $endTime);
                });
            })
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('gtfs_calendar_dates')
                    ->whereColumn('gtfs_calendar_dates.service_id', 'gtfs_trips.service_id')
                    ->where('gtfs_calendar_dates.date', now()->format('Y-m-d'))
                    ->where('gtfs_calendar_dates.exception_type', 1);
            })
            ->selectRaw('DISTINCT gtfs_trips.trip_id');
    }

    public function getTrainsQuery()
    {
        $today = Carbon::now()->format('Y-m-d');
        $currentTime = Carbon::now()->format('H:i:s');
        $endTime = Carbon::now()->addHours(4)->format('H:i:s');

        if (! $this->group) {
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
                DB::raw('SUBSTRING_INDEX(gtfs_trips.trip_id, "-", 1) as train_number'),
                'gtfs_trips.trip_id',
                'gtfs_trips.route_id',
                DB::raw('COALESCE(first_stop.new_departure_time, first_stop.departure_time) as departure'),
                DB::raw('COALESCE(last_stop.new_departure_time, last_stop.arrival_time) as arrival'),
                'gtfs_routes.route_long_name',
                'gtfs_trips.trip_headsign as destination',
                'train_statuses.status as train_status',
                'statuses.status as status_text',
                'statuses.color_rgb',
                DB::raw('COALESCE(first_stop_status.departure_platform, departure_platform.platform_code) as departure_platform'),
                DB::raw('COALESCE(last_stop_status.arrival_platform, arrival_platform.platform_code) as arrival_platform'),
                'first_stop_status.status as realtime_status',
                'first_stop_status.status_color as realtime_status_color',
                'first_stop_status.status_color_hex as realtime_status_color_hex',
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
            ->leftJoin('stop_statuses as first_stop_status', function ($join) {
                $join->on('gtfs_trips.trip_id', '=', 'first_stop_status.trip_id')
                    ->whereRaw('first_stop_status.stop_id = first_stop.stop_id');
            })
            ->leftJoin('stop_statuses as last_stop_status', function ($join) {
                $join->on('gtfs_trips.trip_id', '=', 'last_stop_status.trip_id')
                    ->whereRaw('last_stop_status.stop_id = last_stop.stop_id');
            })
            ->leftJoin('train_statuses', 'gtfs_trips.trip_id', '=', 'train_statuses.trip_id')
            ->leftJoin('statuses', 'train_statuses.status', '=', 'statuses.status')
            ->whereIn('gtfs_trips.route_id', $selectedRoutes)
            ->where(function ($query) use ($currentTime, $endTime) {
                $query->where(function ($q) use ($currentTime, $endTime) {
                    $q->where('first_stop.departure_time', '>=', $currentTime)
                        ->where('first_stop.departure_time', '<=', $endTime);
                })->orWhere(function ($q) use ($currentTime, $endTime) {
                    $q->where('first_stop.new_departure_time', '>=', $currentTime)
                        ->where('first_stop.new_departure_time', '<=', $endTime);
                });
            })
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('gtfs_calendar_dates')
                    ->whereColumn('gtfs_calendar_dates.service_id', 'gtfs_trips.service_id')
                    ->where('gtfs_calendar_dates.date', now()->format('Y-m-d'))
                    ->where('gtfs_calendar_dates.exception_type', 1);
            })
            ->groupBy('gtfs_trips.trip_short_name', 'gtfs_trips.trip_id', 'gtfs_trips.route_id',
                'first_stop.departure_time', 'first_stop.new_departure_time', 'last_stop.arrival_time', 'last_stop.new_departure_time',
                'gtfs_routes.route_long_name', 'gtfs_trips.trip_headsign', 'train_statuses.status', 'statuses.status',
                'statuses.color_rgb', 'departure_platform.platform_code', 'arrival_platform.platform_code',
                'first_stop_status.departure_platform', 'last_stop_status.arrival_platform', 'first_stop_status.status',
                'first_stop_status.status_color', 'first_stop_status.status_color_hex')
            ->orderByRaw('COALESCE(first_stop.new_departure_time, first_stop.departure_time)')
            ->limit(500); // Add safety limit to prevent scanning entire day

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

    // Clear cache when routes are toggled
    public function toggleTableRoute($routeId)
    {
        // Clear cache for this group when routes change
        $this->clearTrainTableCache();

        $route = DB::table('selected_routes')
            ->where('route_id', $routeId)
            ->first();

        if ($route) {
            $newStatus = ! $route->is_active;
            DB::table('selected_routes')
                ->where('route_id', $routeId)
                ->update(['is_active' => $newStatus]);
        } else {
            DB::table('selected_routes')
                ->insert([
                    'route_id' => $routeId,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
        }

        $this->loadSelectedRoutes();
        $this->loadTrains();
    }

    /**
     * Clear the train table cache for instant updates
     */
    private function clearTrainTableCache()
    {
        // Clear current 5-minute interval cache for all pages
        $interval = floor(now()->minute / 5) * 5;
        $previousInterval = floor(now()->subMinutes(5)->minute / 5) * 5;

        for ($page = 1; $page <= 10; $page++) { // Clear up to 10 pages
            $currentCacheKey = "train_table_group_{$this->group->id}_page_{$page}_".now()->format('Y-m-d')."_{$interval}";
            Cache::forget($currentCacheKey);

            // Also clear the previous 5-minute interval cache in case we're at the boundary
            $previousCacheKey = "train_table_group_{$this->group->id}_page_{$page}_".now()->format('Y-m-d')."_{$previousInterval}";
            Cache::forget($previousCacheKey);
        }

        // Clear API cache as well to ensure consistency
        $this->clearApiCache();
    }

    /**
     * Clear the API cache for train data
     */
    private function clearApiCache()
    {
        // Clear current 5-minute interval API cache
        $currentApiCacheKey = 'train_api_today_'.now()->format('Y-m-d_H:').str_pad(floor(now()->minute / 5) * 5, 2, '0', STR_PAD_LEFT);
        Cache::forget($currentApiCacheKey);

        // Also clear the previous 5-minute interval in case we're at the boundary
        $previousApiCacheKey = 'train_api_today_'.now()->subMinutes(5)->format('Y-m-d_H:').str_pad(floor(now()->subMinutes(5)->minute / 5) * 5, 2, '0', STR_PAD_LEFT);
        Cache::forget($previousApiCacheKey);
    }

    public function render()
    {
        try {
            return view('livewire.train-table', [
                'trains' => $this->trains,
                'total' => $this->total,
                'page' => $this->page,
                'perPage' => $this->perPage,
            ]);
        } catch (\Exception $e) {
            Log::error('TrainTable render error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'group_id' => $this->group->id ?? null,
            ]);

            // Return view with error state instead of crashing
            return view('livewire.train-table', [
                'trains' => [],
                'total' => 0,
                'page' => 1,
                'perPage' => $this->perPage,
                'error' => 'Unable to load train data. Please refresh the page.',
            ]);
        }
    }
}
