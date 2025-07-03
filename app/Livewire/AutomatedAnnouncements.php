<?php

namespace App\Livewire;

use App\Models\AutomatedAnnouncementRule;
use App\Models\AviavoxTemplate;
use App\Models\Zone;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Log;

class AutomatedAnnouncements extends Component
{
    use WithPagination;

    public $name = '';
    public $startTime = '08:00';
    public $endTime = '20:00';
    public $intervalMinutes = 40;
    public $daysOfWeek = ['1', '2', '3', '4', '5', '6', '7']; // Default to all days
    public $selectedTemplate = '';
    public $zone = '';
    public $templateVariables = [];
    public $isActive = true;
    public $availableTemplates;
    public $zones;
    public $dayOptions = [
        '1' => 'Monday',
        '2' => 'Tuesday', 
        '3' => 'Wednesday',
        '4' => 'Thursday',
        '5' => 'Friday',
        '6' => 'Saturday',
        '7' => 'Sunday'
    ];

    protected function rules()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'startTime' => 'required',
            'endTime' => 'required|after:startTime',
            'intervalMinutes' => 'required|integer|min:1|max:1440', // Max 24 hours
            'daysOfWeek' => 'required|array|min:1',
            'selectedTemplate' => 'required|exists:aviavox_templates,id',
            'zone' => 'required|string'
        ];

        // Add template variable validation
        if ($this->selectedTemplate) {
            $template = AviavoxTemplate::find($this->selectedTemplate);
            if ($template && !empty($template->variables)) {
                foreach ($template->variables as $variable) {
                    if ($variable !== 'zone') {
                        $rules["templateVariables.$variable"] = 'required';
                    }
                }
            }
        }

        return $rules;
    }

    public function mount()
    {
        $this->availableTemplates = AviavoxTemplate::all();
        $this->zones = Zone::orderBy('value')->get();
    }

    public function updatedSelectedTemplate($value)
    {
        if ($value) {
            $template = AviavoxTemplate::find($value);
            if ($template) {
                // Initialize variables with empty values, excluding 'zone'
                $this->templateVariables = collect($template->variables)
                    ->filter(fn($var) => $var !== 'zone')
                    ->mapWithKeys(fn($var) => [$var => ''])
                    ->toArray();
            }
        } else {
            $this->templateVariables = [];
        }
    }

    public function save()
    {
        $this->validate();

        try {
            AutomatedAnnouncementRule::create([
                'name' => $this->name,
                'start_time' => $this->startTime,
                'end_time' => $this->endTime,
                'interval_minutes' => $this->intervalMinutes,
                'days_of_week' => implode(',', $this->daysOfWeek),
                'aviavox_template_id' => $this->selectedTemplate,
                'zone' => $this->zone,
                'template_variables' => $this->templateVariables,
                'is_active' => $this->isActive
            ]);

            $this->reset([
                'name', 'startTime', 'endTime', 'intervalMinutes', 
                'selectedTemplate', 'zone', 'templateVariables'
            ]);
            
            $this->startTime = '08:00';
            $this->endTime = '20:00';
            $this->intervalMinutes = 40;
            $this->daysOfWeek = ['1', '2', '3', '4', '5', '6', '7'];
            $this->isActive = true;

            session()->flash('success', 'Automated announcement rule created successfully.');
            
        } catch (\Exception $e) {
            Log::error('Error creating automated announcement rule: ' . $e->getMessage());
            session()->flash('error', 'Failed to create rule: ' . $e->getMessage());
        }
    }

    public function toggleRule($ruleId)
    {
        $rule = AutomatedAnnouncementRule::find($ruleId);
        if ($rule) {
            $rule->update(['is_active' => !$rule->is_active]);
        }
    }

    public function deleteRule($ruleId)
    {
        AutomatedAnnouncementRule::find($ruleId)?->delete();
        session()->flash('success', 'Rule deleted successfully.');
    }

    public function render()
    {
        $rules = AutomatedAnnouncementRule::with('aviavoxTemplate')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('livewire.automated-announcements', [
            'rules' => $rules
        ]);
    }
}
