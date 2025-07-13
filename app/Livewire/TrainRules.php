<?php

namespace App\Livewire;

use App\Models\TrainRule;
use App\Models\Status;
use App\Models\AviavoxTemplate;
use App\Models\Zone;
use App\Models\RuleCondition;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Log;

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
    public $valueField = [];
    public $conditions = [];
    public $logicalOperators = ['and' => 'AND', 'or' => 'OR'];
    public $refreshFlag = 0;

    protected function rules()
    {
        $rules = [
            'conditions' => 'required|array|min:1',
            'conditions.*.condition_type' => 'required|in:time_until_departure,time_after_departure,time_until_arrival,time_after_arrival,current_status,train_number',
            'conditions.*.operator' => 'required',
            'conditions.*.value' => 'required',
            'action' => 'required|in:set_status,make_announcement',
        ];

        // Add action-specific validation
        if ($this->action === 'set_status') {
            $rules['actionValue'] = 'required|exists:statuses,id';
        } elseif ($this->action === 'make_announcement') {
            $rules['selectedTemplate'] = 'required|exists:aviavox_templates,id';
            $rules['announcementZone'] = 'required';

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
        }

        return $rules;
    }

    public function mount()
    {
        $this->statuses = Status::orderBy('status')->get();
        $this->availableTemplates = AviavoxTemplate::all();
        $this->zones = Zone::orderBy('value')->get();
        $this->valueField = [
            'type' => 'text',
            'label' => 'Value'
        ];
        $this->actionValue = '';
        $this->addCondition();
    }

    public function addCondition()
    {
        $this->conditions[] = [
            'condition_type' => '',
            'operator' => '',
            'value' => '',
            'logical_operator' => 'and'
        ];
    }

    public function removeCondition($index)
    {
        unset($this->conditions[$index]);
        $this->conditions = array_values($this->conditions);
    }

    public function updatedConditions($value, $key)
    {
        $parts = explode('.', $key);
        $index = $parts[0];
        $field = $parts[1];

        if ($field === 'condition_type') {
            $this->conditions[$index]['operator'] = '';
            $this->conditions[$index]['value'] = '';
            
            // Update the value field based on the new condition type
            switch ($value) {
                case 'time_until_departure':
                case 'time_after_departure':
                case 'time_until_arrival':
                case 'time_after_arrival':
                    $this->conditions[$index]['value'] = '0';
                    break;
                default:
                    $this->conditions[$index]['value'] = '';
            }
        }
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

    public function updatedConditionType($value)
    {
        $this->reset(['operator', 'value', 'action', 'actionValue', 'selectedTemplate', 'templateVariables']);
        
        switch ($value) {
            case 'time_until_departure':
            case 'time_after_departure':
            case 'time_until_arrival':
            case 'time_after_arrival':
                $this->valueField = [
                    'type' => 'number',
                    'label' => 'Minutes',
                    'min' => 0,
                    'step' => 1
                ];
                break;
            case 'current_status':
                $this->valueField = [
                    'type' => 'select',
                    'label' => 'Status',
                    'options' => Status::pluck('status', 'id')->toArray()
                ];
                break;
            case 'train_number':
                $this->valueField = [
                    'type' => 'text',
                    'label' => 'Train Number',
                    'placeholder' => 'Enter train number'
                ];
                break;
            default:
                $this->valueField = [
                    'type' => 'text',
                    'label' => 'Value'
                ];
        }
    }

    public function updatedAction($value)
    {
        
        // Reset the action-specific fields
        $this->reset(['actionValue', 'selectedTemplate', 'announcementZone', 'templateVariables']);
        
        // Force a re-render
        $this->dispatch('action-updated', action: $value);
    }

    public function save()
    {
        $this->validate();

        // Create the rule
        $rule = TrainRule::create([
            'action' => $this->action,
            'action_value' => $this->action === 'set_status' ? $this->actionValue : json_encode([
                'template_id' => $this->selectedTemplate,
                'zone' => $this->announcementZone,
                'variables' => $this->templateVariables
            ]),
            'is_active' => true
        ]);

        // Save each condition
        foreach ($this->conditions as $index => $condition) {
            $ruleCondition = new RuleCondition([
                'train_rule_id' => $rule->id,
                'condition_type' => $condition['condition_type'],
                'operator' => $condition['operator'],
                'value' => $condition['value'],
                'logical_operator' => $index > 0 ? $condition['logical_operator'] : null,
                'order' => $index
            ]);
            $ruleCondition->save();
        }

        // Reset form state and refresh the list
        $this->reset([
            'conditions',
            'action',
            'actionValue',
            'selectedTemplate',
            'announcementZone',
            'templateVariables'
        ]);
        $this->addCondition();
        $this->resetPage();
        session()->flash('message', 'Rule created successfully.');
    }

    public function toggleRule($ruleId)
    {
        $rule = TrainRule::find($ruleId);
        $rule->update(['is_active' => !$rule->is_active]);
    }

    public function deleteRule($ruleId)
    {
        $rule = TrainRule::find($ruleId);
        if ($rule) {
            $rule->delete();
            session()->flash('success', 'Rule deleted successfully.');
            
            // Force component to refresh by updating state
            $this->refreshFlag++;
            $this->resetPage();
        }
    }

    public function render()
    {
        $rules = TrainRule::with(['conditions', 'status'])->orderBy('created_at', 'desc')->paginate(10);
        return view('livewire.train-rules', [
            'rules' => $rules,
            'statuses' => $this->statuses,
            'availableTemplates' => $this->availableTemplates,
            'zones' => $this->zones
        ]);
    }
}
