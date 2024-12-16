<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\GtfsTrip;
use App\Models\GtfsCalendarDate;
use App\Models\GtfsStopTime;
use App\Models\TrainStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class TrainGrid extends Component
{
    public $trains = [];
    public $selectedTrain = null;
    public $newDepartureTime = '';
    public $status = 'on-time';

    public function mount()
    {
        $this->loadTrains();
    }

    private function loadTrains()
    {
        $today = Carbon::now()->format('Y-m-d');
        $currentTime = Carbon::now()->format('H:i:s');

        $trains = GtfsTrip::query()
            ->join('gtfs_calendar_dates', 'gtfs_trips.service_id', '=', 'gtfs_calendar_dates.service_id')
            ->join('gtfs_stop_times', 'gtfs_trips.trip_id', '=', 'gtfs_stop_times.trip_id')
            ->join('gtfs_routes', 'gtfs_trips.route_id', '=', 'gtfs_routes.route_id')
            ->leftJoin('train_statuses', 'gtfs_trips.trip_id', '=', 'train_statuses.trip_id')
            ->where('gtfs_trips.route_id', 'like', 'NLAMA%')
            ->whereDate('gtfs_calendar_dates.date', $today)
            ->where('gtfs_calendar_dates.exception_type', 1)
            ->where('gtfs_stop_times.stop_sequence', 1)
            ->where('gtfs_stop_times.departure_time', '>=', $currentTime)
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
            ->limit(6)
            ->get();

        $this->trains = $trains->map(function ($train) {
            $status = $train->status ? ucfirst($train->status) : 'On-time';
            return [
                'number' => $train->number,
                'trip_id' => $train->trip_id,
                'departure' => substr($train->departure, 0, 5),
                'route_name' => $train->route_long_name,
                'destination' => $train->destination,
                'status' => $status,
                'status_color' => ($status !== 'On-time') ? 'red' : 'neutral'
            ];
        })->toArray();
    }

    public function updateTrainStatus($trainId, $status, $newTime = null)
    {
        $train = GtfsTrip::where('trip_headsign', $trainId)->first();
        
        if ($train) {
            // Update or create status
            TrainStatus::updateOrCreate(
                ['trip_id' => $train->trip_id],
                ['status' => $status]
            );
    
            if ($status === 'delayed' && $newTime) {
                $newTimeWithSeconds = $newTime . ':00';
                GtfsStopTime::where('trip_id', $train->trip_id)
                    ->where('stop_sequence', 1)
                    ->update(['departure_time' => $newTimeWithSeconds]);
            }
    
            // Update the local collection for the view
            $trainIndex = array_search($trainId, array_column($this->trains, 'number'));
    
            if ($trainIndex !== false) {
                $this->trains[$trainIndex]['status'] = ucfirst($status);
                $this->trains[$trainIndex]['status_color'] = 'red';
                
                if ($status === 'delayed' && $newTime) {
                    $this->trains[$trainIndex]['departure'] = $newTime;
                }
            }
    
            $this->loadTrains();
        }
    }

    public function render()
    {
        return view('livewire.train-grid');
    }
}
