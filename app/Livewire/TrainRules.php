<?php

namespace App\Livewire;

use App\Models\TrainRule;
use App\Models\Status;
use App\Models\AviavoxTemplate;
use App\Models\Zone;
use Livewire\Component;
use Livewire\WithPagination;

class TrainRules extends Component
{
    use WithPagination;

    public $conditionType = '';
    public $operator = '';
    public $value = '';
    public $action = '';
    public $actionValue = '';
    public $announcementText = '';
    public $isActive = true;
    public $statuses;
    public $selectedAnnouncement = '';
    public $selectedTemplate = '';
    public $announcementZone = '';
    public $templateVariables = [];
    public $availableTemplates;
    public $zones;

    protected function rules()
    {
        $rules = [
            'conditionType' => 'required|in:time_until_departure,time_since_arrival,platform_change,delay_duration,current_status,time_of_day',
            'operator' => [
                'required',
                function ($attribute, $value, $fail) {
                    $validOperators = match ($this->conditionType) {
                        'platform_change' => ['='],
                        'current_status' => ['='],
                        default => ['>', '<', '=']
                    };
                    
                    if (!in_array($value, $validOperators)) {
                        $fail('The selected operator is invalid for this condition type.');
                    }
                }
            ],
            'value' => [
                'required',
                function ($attribute, $value, $fail) {
                    switch ($this->conditionType) {
                        case 'time_until_departure':
                        case 'time_since_arrival':
                        case 'delay_duration':
                            if (!is_numeric($value) || $value < 0) {
                                $fail('The value must be a positive number of minutes.');
                            }
                            break;
                        case 'time_of_day':
                            if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $value)) {
                                $fail('The value must be a valid time in 24-hour format (HH:MM).');
                            }
                            break;
                        case 'current_status':
                            if (!Status::where('id', $value)->exists()) {
                                $fail('The selected status is invalid.');
                            }
                            break;
                    }
                }
            ],
            'action' => 'required|in:set_status,make_announcement',
            'actionValue' => [
                'required_if:action,set_status',
                'exists:statuses,id'
            ],
            'selectedAnnouncement' => 'required_if:action,make_announcement',
            'isActive' => 'boolean',
            'selectedTemplate' => 'required_if:action,make_announcement|exists:aviavox_templates,id',
            'announcementZone' => 'required_if:action,make_announcement',
        ];

        // Add validation rules for template variables dynamically
        if ($this->action === 'make_announcement' && $this->selectedTemplate) {
            $template = AviavoxTemplate::find($this->selectedTemplate);
            foreach ($template->variables as $variable) {
                $rules["templateVariables.{$variable}"] = 'required';
            }
        }

        return $rules;
    }

    public function mount()
    {
        $this->statuses = Status::orderBy('status')->get();
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
                    ->filter(fn($var) => $var !== 'zone') // Exclude zone from variables
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

        if ($this->action === 'make_announcement') {
            // Add the selected zone to the variables array
            $variables = array_merge($this->templateVariables, ['zone' => $this->announcementZone]);
            
            $announcementData = [
                'template_id' => $this->selectedTemplate,
                'variables' => $variables
            ];
            $actionValue = json_encode($announcementData);
        } else {
            $actionValue = $this->actionValue;
        }

        TrainRule::create([
            'condition_type' => $this->conditionType,
            'operator' => $this->operator,
            'value' => $this->value,
            'action' => $this->action,
            'action_value' => $actionValue,
            'is_active' => $this->isActive,
        ]);

        $this->reset(['conditionType', 'operator', 'value', 'action', 'actionValue', 
                     'selectedTemplate', 'announcementZone', 'templateVariables']);
        session()->flash('success', 'Rule created successfully.');
    }

    public function toggleRule($ruleId)
    {
        $rule = TrainRule::find($ruleId);
        $rule->update(['is_active' => !$rule->is_active]);
    }

    public function deleteRule($ruleId)
    {
        TrainRule::find($ruleId)->delete();
        session()->flash('success', 'Rule deleted successfully.');
    }

    public function updatedConditionType()
    {
        // Reset operator and value when condition type changes
        $this->operator = '';
        $this->value = '';
    }

    public function render()
    {
        $valueField = match ($this->conditionType) {
            'current_status' => [
                'type' => 'select',
                'options' => Status::pluck('status', 'id'),
                'label' => 'Status'
            ],
            'time_of_day' => [
                'type' => 'time',
                'label' => 'Time (24h)'
            ],
            default => [
                'type' => 'number',
                'label' => 'Value (minutes)'
            ],
        };

        return view('livewire.train-rules', [
            'rules' => TrainRule::with('status')->orderBy('created_at', 'desc')->paginate(10),
            'valueField' => $valueField
        ]);
    }
}
