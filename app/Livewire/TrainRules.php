<?php

namespace App\Livewire;

use App\Models\TrainRule;
use App\Models\Status;
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
    public $isActive = true;
    public $statuses;

    protected $rules = [
        'conditionType' => 'required|in:time_until_departure',
        'operator' => 'required|in:>,<,=',
        'value' => 'required|integer|min:1',
        'action' => 'required|in:set_status',
        'actionValue' => 'required|in:delayed,cancelled,on-time',
        'isActive' => 'boolean'
    ];

    public function mount()
    {
        $this->statuses = Status::orderBy('status')->get();
    }

    public function save()
    {
        $this->validate();

        TrainRule::create([
            'condition_type' => $this->conditionType,
            'operator' => $this->operator,
            'value' => $this->value,
            'action' => $this->action,
            'action_value' => $this->actionValue,
            'is_active' => $this->isActive,
        ]);

        $this->reset(['conditionType', 'operator', 'value', 'action', 'actionValue']);
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

    public function render()
    {
        return view('livewire.train-rules', [
            'rules' => TrainRule::orderBy('created_at', 'desc')->paginate(10)
        ]);
    }
}
