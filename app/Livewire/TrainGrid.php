<?php

namespace App\Livewire;

use Livewire\Component;

class TrainGrid extends Component
{
    public function render()
    {
        // You might want to fetch this from a database
        $trains = [
            [
                'number' => '9147',
                'departure' => '14:47',
                'status' => 'Check-in Open',
                'status_color' => 'green'
            ],
            [
                'number' => '9157',
                'departure' => '14:47',
                'status' => 'Cancelled',
                'status_color' => 'red'
            ],
            [
                'number' => '9167',
                'departure' => '14:47',
                'status' => 'On-time',
                'status_color' => 'neutral'
            ],
            [
                'number' => '9177',
                'departure' => '14:47',
                'status' => 'On-time',
                'status_color' => 'neutral'
            ],
        ];

        return view('livewire.train-grid', [
            'trains' => $trains
        ]);
    }
}
