<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'action',
        'action_value',
        'is_active',
        'priority',
        'execution_mode',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'action' => 'array',
        'action_value' => 'array',
    ];

    public function conditions()
    {
        return $this->hasMany(RuleCondition::class, 'train_rule_id')->orderBy('order');
    }

    public function status()
    {
        return $this->belongsTo(Status::class, 'action_value');
    }

    /**
     * Get actions array, supporting both old single action and new array format
     */
    public function getActions()
    {
        return is_array($this->action) ? $this->action : [$this->action];
    }

    public function shouldTrigger($train)
    {
        $conditions = $this->conditions;

        if ($conditions->isEmpty()) {
            return false;
        }

        $result = $conditions->first()->evaluate($train);

        foreach ($conditions->skip(1) as $condition) {
            if ($condition->logical_operator === 'and') {
                $result = $result && $condition->evaluate($train);
            } else {
                $result = $result || $condition->evaluate($train);
            }
        }

        return $result;
    }

    private function compare($value1, $value2)
    {
        switch ($this->operator) {
            case '>':
                return $value1 > $value2;
            case '<':
                return $value1 < $value2;
            case '=':
                return $value1 == $value2;
            default:
                return false;
        }
    }
}
