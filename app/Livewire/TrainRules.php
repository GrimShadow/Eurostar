<?php

namespace App\Livewire;

use App\Models\AviavoxTemplate;
use App\Models\CheckInStatus;
use App\Models\RuleCondition;
use App\Models\Status;
use App\Models\TrainRule;
use App\Models\Zone;
use Illuminate\Support\Facades\Log;
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

    public $priority = 0;

    public $executionMode = 'first_match';

    public $actions = [];

    public $actionValues = [];

    public $statuses;

    public $checkInStatuses;

    public $selectedAnnouncement = '';

    public $selectedTemplate = '';

    public $announcementZone = '';

    public $zoneSelectionStrategy = 'group_zones'; // New: 'group_zones' or 'specific_zone'

    public $templateVariables = [];

    public $variableTypes = []; // Track whether each variable is 'manual' or 'dynamic'

    public $availableTemplates;

    public $zones;

    public $valueField = [];

    public $conditions = [];

    public $logicalOperators = ['and' => 'AND', 'or' => 'OR'];

    public $tableKey = 0;

    public $editingRuleId = null;

    protected function rules()
    {
        $rules = [
            'conditions' => 'required|array|min:1',
            'conditions.*.condition_type' => 'required|in:time_until_departure,time_after_departure,time_until_arrival,time_after_arrival,time_since_arrival,platform_change,delay_duration,current_status,check_in_status,time_of_day,train_number,delay_minutes,delay_percentage,platform_changed,specific_platform,is_cancelled,has_realtime_update,route_id,direction_id,destination_station,time_range,day_of_week,is_peak_time,wheelchair_accessible,minutes_until_check_in_starts,departure_time,actual_departure_time,arrival_time,departure_platform,arrival_platform,route_name,stop_name',
            'conditions.*.operator' => 'required',
            'conditions.*.value' => 'required',
            'action' => 'required|in:set_status,make_announcement,update_platform,set_check_in_status',
        ];

        // Add condition-specific validation for value field
        foreach ($this->conditions as $index => $condition) {
            if (isset($condition['condition_type'])) {
                if ($condition['condition_type'] === 'current_status') {
                    $rules["conditions.{$index}.value"] = 'required|exists:statuses,id';
                } elseif ($condition['condition_type'] === 'check_in_status') {
                    $rules["conditions.{$index}.value"] = 'required|exists:check_in_statuses,id';
                } elseif ($condition['condition_type'] === 'time_of_day') {
                    $rules["conditions.{$index}.value"] = 'required|regex:/^([01][0-9]|2[0-3]):[0-5][0-9]$/';
                } elseif (in_array($condition['condition_type'], ['departure_time', 'actual_departure_time', 'arrival_time'])) {
                    $rules["conditions.{$index}.value"] = 'required|regex:/^([01][0-9]|2[0-3]):[0-5][0-9]$/';
                }
            }
        }

        // Add action-specific validation
        if ($this->action === 'set_status') {
            $rules['actionValue'] = 'required|exists:statuses,id';
        } elseif ($this->action === 'set_check_in_status') {
            $rules['actionValue'] = 'required|exists:check_in_statuses,id';
        } elseif ($this->action === 'make_announcement') {
            $rules['selectedTemplate'] = 'required|exists:aviavox_templates,id';

            // Zone validation depends on strategy
            if ($this->zoneSelectionStrategy === 'specific_zone') {
                $rules['announcementZone'] = 'required';
            }

            if ($this->selectedTemplate) {
                $template = AviavoxTemplate::find($this->selectedTemplate);
                if ($template && ! empty($template->variables)) {
                    foreach ($template->variables as $variable) {
                        if ($variable !== 'zone') {
                            // Only require validation for manual variables, not dynamic ones
                            $variableType = $this->variableTypes[$variable] ?? 'manual';
                            if ($variableType === 'manual') {
                                $rules["templateVariables.$variable"] = 'required';
                            }
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
        $this->checkInStatuses = CheckInStatus::orderByRaw('LOWER(status) ASC')->get();
        $this->availableTemplates = AviavoxTemplate::all();
        $this->zones = Zone::orderBy('value')->get();
        $this->zoneSelectionStrategy = 'group_zones'; // Default to using group zones
        $this->valueField = [
            'type' => 'text',
            'label' => 'Value',
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
            'logical_operator' => 'and',
        ];
    }

    /**
     * Clear announcementZone when switching back to group_zones
     */
    public function updatedZoneSelectionStrategy($value)
    {
        if ($value === 'group_zones') {
            $this->announcementZone = '';
        }
    }

    public function removeCondition($index)
    {
        unset($this->conditions[$index]);
        $this->conditions = array_values($this->conditions);
    }

    public function toggleDayOfWeek($index, $dayValue)
    {
        // Ensure value is an array
        $currentValue = $this->conditions[$index]['value'] ?? [];
        if (! is_array($currentValue) && ! empty($currentValue)) {
            $currentValue = explode(',', $currentValue);
        } elseif (! is_array($currentValue)) {
            $currentValue = [];
        }

        // Toggle the day value
        if (in_array($dayValue, $currentValue)) {
            // Remove the day
            $currentValue = array_values(array_diff($currentValue, [$dayValue]));
        } else {
            // Add the day
            $currentValue[] = $dayValue;
            sort($currentValue);
        }

        $this->conditions[$index]['value'] = $currentValue;
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
                case 'minutes_until_check_in_starts':
                    $this->conditions[$index]['value'] = '0';
                    break;
                case 'time_of_day':
                case 'departure_time':
                case 'actual_departure_time':
                case 'arrival_time':
                    $this->conditions[$index]['value'] = '';
                    break;
                case 'day_of_week':
                    $this->conditions[$index]['value'] = [];
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
            if ($template && ! empty($template->variables)) {
                // Initialize variables with empty values, excluding 'zone'
                $variables = collect($template->variables)->filter(fn ($var) => $var !== 'zone');

                $this->templateVariables = $variables->mapWithKeys(fn ($var) => [$var => ''])->toArray();
                $this->variableTypes = $variables->mapWithKeys(fn ($var) => [$var => 'manual'])->toArray();

                // Force a re-render by updating a property that triggers reactivity
                $this->tableKey++;
            } else {
                $this->templateVariables = [];
                $this->variableTypes = [];
            }
        } else {
            $this->templateVariables = [];
            $this->variableTypes = [];
        }
    }

    public function updatedConditionType($value)
    {
        $this->reset(['operator', 'value', 'action', 'actionValue']);

        switch ($value) {
            case 'time_until_departure':
            case 'time_after_departure':
            case 'time_until_arrival':
            case 'time_after_arrival':
            case 'delay_minutes':
            case 'delay_percentage':
            case 'minutes_until_check_in_starts':
                $this->valueField = [
                    'type' => 'number',
                    'label' => 'Minutes',
                    'min' => 0,
                    'step' => 1,
                ];
                break;
            case 'current_status':
                $this->valueField = [
                    'type' => 'select',
                    'label' => 'Status',
                    'options' => Status::pluck('status', 'id')->toArray(),
                ];
                break;
            case 'train_number':
                $this->valueField = [
                    'type' => 'text',
                    'label' => 'Train Number',
                    'placeholder' => 'Enter train number',
                ];
                break;
            case 'platform_changed':
            case 'is_cancelled':
            case 'has_realtime_update':
            case 'is_peak_time':
            case 'wheelchair_accessible':
                $this->valueField = [
                    'type' => 'boolean',
                    'label' => 'Value',
                ];
                break;
            case 'specific_platform':
            case 'route_id':
            case 'direction_id':
            case 'destination_station':
                $this->valueField = [
                    'type' => 'text',
                    'label' => 'Value',
                    'placeholder' => 'Enter value',
                ];
                break;
            case 'time_of_day':
            case 'departure_time':
            case 'actual_departure_time':
            case 'arrival_time':
                $this->valueField = [
                    'type' => 'time',
                    'label' => 'Time (HH:MM)',
                ];
                break;
            case 'departure_platform':
            case 'arrival_platform':
                $this->valueField = [
                    'type' => 'text',
                    'label' => 'Platform',
                    'placeholder' => 'Enter platform number',
                ];
                break;
            case 'route_name':
                $this->valueField = [
                    'type' => 'text',
                    'label' => 'Route Name',
                    'placeholder' => 'Enter route name',
                ];
                break;
            case 'stop_name':
                $this->valueField = [
                    'type' => 'text',
                    'label' => 'Stop Name',
                    'placeholder' => 'Enter stop name',
                ];
                break;
            case 'time_range':
                $this->valueField = [
                    'type' => 'range',
                    'label' => 'Time Range',
                    'placeholder' => 'Start time - End time',
                ];
                break;
            case 'day_of_week':
                $this->valueField = [
                    'type' => 'select',
                    'label' => 'Days',
                    'options' => [
                        '1' => 'Monday',
                        '2' => 'Tuesday',
                        '3' => 'Wednesday',
                        '4' => 'Thursday',
                        '5' => 'Friday',
                        '6' => 'Saturday',
                        '0' => 'Sunday',
                    ],
                ];
                break;
            default:
                $this->valueField = [
                    'type' => 'text',
                    'label' => 'Value',
                ];
        }
    }

    public function updatedAction($value)
    {
        if ($value === 'set_status') {
            // Reset announcement-specific fields when switching to set_status
            $this->reset(['actionValue', 'selectedTemplate', 'announcementZone', 'zoneSelectionStrategy', 'templateVariables', 'variableTypes']);
        } elseif ($value === 'set_check_in_status') {
            // Reset other action-specific fields when switching to set_check_in_status
            $this->reset(['selectedTemplate', 'announcementZone', 'zoneSelectionStrategy', 'templateVariables', 'variableTypes']);
        } elseif ($value === 'make_announcement') {
            // Only reset status-specific fields when switching to make_announcement
            $this->reset(['actionValue']);
            // Set default zone strategy for announcements
            $this->zoneSelectionStrategy = 'group_zones';
        }
    }

    public function save()
    {
        $this->validate();

        // Prepare action value based on action type
        if ($this->action === 'set_status') {
            $actionValue = $this->actionValue;
        } elseif ($this->action === 'set_check_in_status') {
            $actionValue = $this->actionValue;
        } elseif ($this->action === 'update_platform') {
            $actionValue = $this->actionValue;
        } else {
            // make_announcement
            $actionValue = json_encode([
                'template_id' => $this->selectedTemplate,
                'zone_strategy' => $this->zoneSelectionStrategy,
                'zone' => $this->zoneSelectionStrategy === 'specific_zone' ? $this->announcementZone : null,
                'variables' => $this->processTemplateVariables(),
                'variable_types' => $this->variableTypes,
            ]);
        }

        // Update existing rule or create new one
        if ($this->editingRuleId) {
            $rule = TrainRule::find($this->editingRuleId);
            if (! $rule) {
                session()->flash('error', 'Rule not found.');

                return;
            }

            // Update the rule
            $rule->update([
                'action' => $this->action,
                'action_value' => $actionValue,
                'is_active' => $this->isActive,
                'priority' => $this->priority,
                'execution_mode' => $this->executionMode,
            ]);

            // Delete existing conditions
            RuleCondition::where('train_rule_id', $rule->id)->delete();

            $message = 'Rule updated successfully.';
        } else {
            // Create new rule
            $rule = TrainRule::create([
                'action' => $this->action,
                'action_value' => $actionValue,
                'is_active' => $this->isActive,
                'priority' => $this->priority,
                'execution_mode' => $this->executionMode,
            ]);

            $message = 'Rule created successfully.';
        }

        // Clear train rules cache to ensure new/updated rules are loaded immediately
        $this->clearTrainRulesCache();

        // Save each condition
        foreach ($this->conditions as $index => $condition) {
            // Calculate appropriate tolerance based on condition type and operator
            $tolerance = $this->calculateTolerance($condition);

            // Convert day_of_week array to comma-separated string
            $value = $condition['value'];
            if ($condition['condition_type'] === 'day_of_week' && is_array($value)) {
                $value = implode(',', $value);
            }

            // Convert time_of_day from HH:MM to H:i:s format
            if ($condition['condition_type'] === 'time_of_day' && ! empty($value)) {
                // If value is in HH:MM format, append :00 for seconds
                if (preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/', $value)) {
                    $value = $value.':00';
                }
            }

            $ruleCondition = new RuleCondition([
                'train_rule_id' => $rule->id,
                'condition_type' => $condition['condition_type'],
                'operator' => $condition['operator'],
                'value' => $value,
                'logical_operator' => $index > 0 ? $condition['logical_operator'] : null,
                'order' => $index,
                'tolerance_minutes' => $tolerance,
            ]);
            $ruleCondition->save();
        }

        // Reset form state and refresh the list
        $this->resetForm();
        $this->resetPage();
        $this->tableKey++;
        session()->flash('message', $message);
    }

    public function toggleRule($ruleId)
    {
        $rule = TrainRule::find($ruleId);
        $rule->update(['is_active' => ! $rule->is_active]);

        // Clear cache so rule status changes take effect immediately
        $this->clearTrainRulesCache();

        // Force table re-render by updating the key
        $this->tableKey++;
    }

    public function deleteRule($ruleId)
    {
        $rule = TrainRule::find($ruleId);
        if ($rule) {
            $rule->delete();
            session()->flash('success', 'Rule deleted successfully.');

            // Clear cache so deleted rules are removed immediately
            $this->clearTrainRulesCache();

            // Force table re-render by updating the key
            $this->tableKey++;
        }
    }

    /**
     * Reset all form fields to their initial state
     */
    public function resetForm()
    {
        // Reset all form-related properties
        $this->reset([
            'conditionType',
            'operator',
            'value',
            'action',
            'actionValue',
            'announcementText',
            'isActive',
            'selectedAnnouncement',
            'selectedTemplate',
            'announcementZone',
            'zoneSelectionStrategy',
            'templateVariables',
            'variableTypes',
            'conditions',
            'editingRuleId',
        ]);

        // Reset valueField to initial state
        $this->valueField = [
            'type' => 'text',
            'label' => 'Value',
        ];

        // Reset defaults
        $this->priority = 0;
        $this->executionMode = 'first_match';
        $this->zoneSelectionStrategy = 'group_zones';

        // Add a fresh condition
        $this->addCondition();

        // Clear any validation errors
        $this->resetValidation();
    }

    /**
     * Load a rule for editing
     */
    public function editRule($ruleId)
    {
        $rule = TrainRule::with('conditions')->find($ruleId);

        if (! $rule) {
            session()->flash('error', 'Rule not found.');

            return;
        }

        // Set editing mode
        $this->editingRuleId = $rule->id;

        // Load rule properties
        // Note: action is cast to array in model, but we store it as a string
        // Work with the cast value directly - Laravel handles the JSON decoding
        $modelAction = $rule->action;

        // Handle array cast - if it's an array, take first element, otherwise use as string
        $extractedAction = '';
        if (is_array($modelAction) && ! empty($modelAction)) {
            $extractedAction = (string) ($modelAction[0] ?? '');
        } elseif (is_string($modelAction)) {
            $extractedAction = $modelAction;
        }

        // Ensure action is set as a clean string to match option values exactly
        $this->action = trim($extractedAction);

        $this->isActive = (bool) $rule->is_active;
        $this->priority = (int) ($rule->priority ?? 0);
        $this->executionMode = (string) ($rule->execution_mode ?? 'first_match');

        // Load conditions - build array first, then assign all at once
        // This ensures Livewire detects the change properly
        $loadedConditions = [];
        foreach ($rule->conditions->sortBy('order') as $condition) {
            $value = trim((string) ($condition->value ?? ''));
            // Convert day_of_week comma-separated string back to array for checkboxes
            if ($condition->condition_type === 'day_of_week' && ! empty($value)) {
                $value = explode(',', $value);
            }

            // Convert time_of_day from H:i:s to HH:MM format for time input
            if ($condition->condition_type === 'time_of_day' && ! empty($value)) {
                // If value is in H:i:s format, remove seconds
                if (preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/', $value)) {
                    $value = substr($value, 0, 5); // Remove :SS part
                }
            }

            $loadedConditions[] = [
                'condition_type' => trim((string) ($condition->condition_type ?? '')),
                'operator' => trim((string) ($condition->operator ?? '')),
                'value' => $value,
                'logical_operator' => trim((string) ($condition->logical_operator ?? 'and')),
            ];
        }

        // Ensure array is properly indexed (sequential numeric keys starting from 0)
        $loadedConditions = array_values($loadedConditions);

        // If no conditions, add one
        if (empty($loadedConditions)) {
            $loadedConditions = [[
                'condition_type' => '',
                'operator' => '',
                'value' => '',
                'logical_operator' => 'and',
            ]];
        }

        // Assign the complete array at once - this helps Livewire detect the change
        $this->conditions = $loadedConditions;

        // Load action-specific data based on the loaded action
        // Note: action_value is cast to array in the model, but for set_status it's just an ID
        $modelActionValue = $rule->action_value;

        if ($this->action === 'set_status') {
            // For set_status, action_value is the status ID (may be stored as string or int)
            if (is_array($modelActionValue) && ! empty($modelActionValue)) {
                $this->actionValue = (string) ($modelActionValue[0] ?? '');
            } elseif (is_string($modelActionValue) || is_numeric($modelActionValue)) {
                $this->actionValue = (string) $modelActionValue;
            } else {
                $this->actionValue = '';
            }
        } elseif ($this->action === 'set_check_in_status') {
            // For set_check_in_status, action_value is the check-in status ID (may be stored as string or int)
            if (is_array($modelActionValue) && ! empty($modelActionValue)) {
                $this->actionValue = (string) ($modelActionValue[0] ?? '');
            } elseif (is_string($modelActionValue) || is_numeric($modelActionValue)) {
                $this->actionValue = (string) $modelActionValue;
            } else {
                $this->actionValue = '';
            }
        } elseif ($this->action === 'make_announcement') {
            // For make_announcement, action_value is JSON/array with template and zone info
            if (is_array($modelActionValue)) {
                $announcementData = $modelActionValue;
            } else {
                $announcementData = json_decode((string) $modelActionValue, true);
            }

            if ($announcementData) {
                $this->selectedTemplate = (string) ($announcementData['template_id'] ?? '');
                $this->zoneSelectionStrategy = (string) ($announcementData['zone_strategy'] ?? 'group_zones');
                $this->announcementZone = (string) ($announcementData['zone'] ?? '');

                // Load template variables and types
                $this->templateVariables = $announcementData['variables'] ?? [];
                $this->variableTypes = $announcementData['variable_types'] ?? [];

                // Trigger template selection to load variables if template is set
                if ($this->selectedTemplate) {
                    $this->updatedSelectedTemplate($this->selectedTemplate);
                    // Restore the variable values and types after template loads
                    $this->templateVariables = $announcementData['variables'] ?? [];
                    $this->variableTypes = $announcementData['variable_types'] ?? [];
                }
            }
        } elseif ($this->action === 'update_platform') {
            // For update_platform, action_value is the platform string
            if (is_array($modelActionValue) && ! empty($modelActionValue)) {
                $this->actionValue = (string) ($modelActionValue[0] ?? '');
            } elseif (is_string($modelActionValue)) {
                $this->actionValue = $modelActionValue;
            } else {
                $this->actionValue = '';
            }
        }

        // Force Livewire to detect all changes
        // Increment tableKey to force view update
        $this->tableKey++;
    }

    /**
     * Cancel editing and reset form
     */
    public function cancelEdit()
    {
        $this->resetForm();
    }

    /**
     * Clear train rules cache to ensure immediate updates
     */
    private function clearTrainRulesCache()
    {
        try {
            // Clear active rules cache used by ProcessTrainRules command
            \Illuminate\Support\Facades\Cache::forget('active_train_rules');
            \Illuminate\Support\Facades\Cache::forget('active_train_rules_2min');

            // Clear any time-based rule caches
            $now = now();
            for ($i = 0; $i < 5; $i++) {
                $cacheKey = 'active_train_rules_'.$now->copy()->addMinutes($i)->format('Y-m-d_H:i');
                \Illuminate\Support\Facades\Cache::forget($cacheKey);
            }

            Log::info('Train rules cache cleared after rule modification');
        } catch (\Exception $e) {
            Log::warning('Failed to clear train rules cache: '.$e->getMessage());
        }
    }

    /**
     * Process template variables, replacing dynamic ones with special markers
     */
    private function processTemplateVariables()
    {
        $processedVariables = [];

        foreach ($this->templateVariables as $variable => $value) {
            $variableType = $this->variableTypes[$variable] ?? 'manual';

            if ($variableType === 'dynamic') {
                // Store a special marker for dynamic variables that will be replaced at runtime
                $processedVariables[$variable] = "{{DYNAMIC_$variable}}";
            } else {
                // Store the manual value as-is
                $processedVariables[$variable] = $value;
            }
        }

        return $processedVariables;
    }

    /**
     * Calculate appropriate tolerance for a condition based on type and operator
     */
    private function calculateTolerance($condition)
    {
        // For time-based conditions with equality operator, use tolerance to handle timing issues
        if (in_array($condition['condition_type'], ['time_until_departure', 'time_after_departure', 'time_until_arrival', 'time_after_arrival'])) {
            if ($condition['operator'] === '=') {
                // For equality, use 1-minute tolerance window to handle the rules engine running every minute
                return 1;
            }
        }

        // For non-time conditions or non-equality operators, no tolerance needed
        return 0;
    }

    public function render()
    {
        $rules = TrainRule::with(['conditions', 'status'])->orderBy('created_at', 'desc')->paginate(10);

        return view('livewire.train-rules', [
            'rules' => $rules,
            'statuses' => $this->statuses,
            'checkInStatuses' => $this->checkInStatuses,
            'availableTemplates' => $this->availableTemplates,
            'zones' => $this->zones,
        ]);
    }
}
