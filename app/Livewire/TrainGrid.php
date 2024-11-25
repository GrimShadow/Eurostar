<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\GtfsTrip;
use App\Models\GtfsCalendarDate;
use App\Models\GtfsStopTime;
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

        $trains = GtfsTrip::query()
            ->join('gtfs_calendar_dates', 'gtfs_trips.service_id', '=', 'gtfs_calendar_dates.service_id')
            ->join('gtfs_stop_times', 'gtfs_trips.trip_id', '=', 'gtfs_stop_times.trip_id')
            ->join('gtfs_routes', 'gtfs_trips.route_id', '=', 'gtfs_routes.route_id')
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
                'gtfs_trips.trip_headsign as destination'
            ])
            ->orderBy('gtfs_stop_times.departure_time')
            ->get();

        $this->trains = $trains->map(function ($train) {
            return [
                'number' => $train->number,
                'trip_id' => $train->trip_id,
                'departure' => substr($train->departure, 0, 5),
                'route_name' => $train->route_long_name,
                'destination' => $train->destination,
                'status' => 'On-time',
                'status_color' => 'neutral'
            ];
        })->toArray();
    }

    public function updateTrainStatus($trainId, $status, $newTime = null)
    {
        $train = GtfsTrip::where('trip_headsign', $trainId)->first();
        
        if ($train) {
            if ($status === 'delayed' && $newTime) {
                // Format the time to include seconds
                $newTimeWithSeconds = $newTime . ':00';
                
                // Update the departure time in gtfs_stop_times
                GtfsStopTime::where('trip_id', $train->trip_id)
                    ->where('stop_sequence', 1)
                    ->update(['departure_time' => $newTimeWithSeconds]);

                // Refresh the trains data
                $this->loadTrains();
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
        }
    }

    public function render()
    {
        return view('livewire.train-grid');
    }
}
