<?php

namespace App\Livewire;

use App\Models\Announcement;
use App\Models\AviavoxAnnouncement;
use App\Models\AviavoxSetting; // Import the AviavoxSetting model
use Livewire\Component;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CreateAnnouncement extends Component
{
    public $type = '';
    public $message = '';
    public $scheduled_time = '';
    public $recurrence = '';
    public $author = '';
    public $area = '';
    public $selectedAnnouncement = '';
    public $audioAnnouncements;

    protected $rules = [
        'type' => 'required|in:audio,text',
        'message' => 'required_if:type,text',
        'scheduled_time' => 'required',
        'recurrence' => 'nullable',
        'author' => 'required',
        'area' => 'required',
        'selectedAnnouncement' => 'required_if:type,audio',
    ];

    public function mount()
    {
        $this->audioAnnouncements = AviavoxAnnouncement::all();
    }

    public function updatedType()
    {
        $this->selectedAnnouncement = '';
        $this->message = '';
    }

    public function save()
    {
        $this->validate();

        $message = $this->type === 'text' ? $this->message : ($this->type === 'audio' ? $this->audioAnnouncements->find($this->selectedAnnouncement)?->name : null);

        $announcement = Announcement::create([
            'type' => $this->type,
            'message' => $message,
            'scheduled_time' => $this->scheduled_time,
            'recurrence' => $this->recurrence,
            'author' => $this->author,
            'area' => $this->area,
            'status' => 'Pending'
        ]);

        if ($this->type === 'audio' && $this->selectedAnnouncement) {
            $selected = AviavoxAnnouncement::find($this->selectedAnnouncement);

            if ($selected) {
                // Fetch connection details from the aviavox_settings table
                $settings = AviavoxSetting::first();
                if (!$settings) {
                    session()->flash('error', 'Aviavox settings are not configured.');
                    return;
                }

                // Construct the URL using the IP address and port from the settings
                $url = "http://{$settings->ip_address}:{$settings->port}";

                $xml = '<AIP>
                    <MessageID>AnnouncementTriggerRequest</MessageID>
                    <MessageData>
                        <AnnouncementData>
                            <Item ID="MessageName" Value="' . $selected->item_id . '"/>
                        </AnnouncementData>
                    </MessageData>
                </AIP>';

                try {
                    $response = Http::withHeaders([
                        'Content-Type' => 'application/xml',
                    ])->post($url, $xml);

                    // Log the response status and body
                    Log::info('Aviavox Response', [
                        'status' => $response->status(),
                        'body' => $response->body()
                    ]);

                    if (!$response->successful()) {
                        session()->flash('error', 'Failed to send the announcement.');
                    }
                } catch (\Exception $e) {
                    // Log the exception message if there's an error
                    Log::error('Aviavox Connection Error: ' . $e->getMessage());
                    session()->flash('error', 'Failed to connect to Aviavox server.');
                }
            }
        }

        $this->reset(['type', 'message', 'scheduled_time', 'recurrence', 'author', 'area', 'selectedAnnouncement']);
        $this->dispatch('announcement-created');
        $this->dispatch('close-modal');
    }

    public function render()
    {
        return view('livewire.create-announcement');
    }
}

