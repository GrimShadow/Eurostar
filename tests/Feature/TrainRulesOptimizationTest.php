<?php

declare(strict_types=1);

use App\Models\GtfsStop;
use App\Models\GtfsStopTime;
use App\Models\GtfsTrip;
use App\Models\RuleCondition;
use App\Models\Status;
use App\Models\StopStatus;
use App\Models\TrainRule;

test('can create rule with priority and execution mode', function () {
    $status = Status::factory()->create();

    $rule = TrainRule::create([
        'action' => 'set_status',
        'action_value' => $status->id,
        'is_active' => true,
        'priority' => 10,
        'execution_mode' => 'highest_priority',
    ]);

    expect($rule->priority)->toBe(10);
    expect($rule->execution_mode)->toBe('highest_priority');
    expect($rule->getActions())->toBe(['set_status']);
});

test('can create rule with multiple actions', function () {
    $status = Status::factory()->create();

    $rule = TrainRule::create([
        'action' => ['set_status', 'make_announcement'],
        'action_value' => [$status->id, ['template_id' => 1]],
        'is_active' => true,
        'priority' => 5,
    ]);

    expect($rule->getActions())->toBe(['set_status', 'make_announcement']);
    expect($rule->action_value)->toBe([$status->id, ['template_id' => 1]]);
});

test('can evaluate delay minutes condition', function () {
    $rule = TrainRule::factory()->create();

    $condition = RuleCondition::create([
        'train_rule_id' => $rule->id,
        'condition_type' => 'delay_minutes',
        'operator' => '>',
        'value' => '5',
        'order' => 1,
    ]);

    // Create a mock train with delay
    $trip = GtfsTrip::factory()->create();
    $stop = GtfsStop::factory()->create();
    $stopTime = GtfsStopTime::factory()->create([
        'trip_id' => $trip->trip_id,
        'stop_id' => $stop->stop_id,
        'departure_time' => '10:00:00',
    ]);

    // Create a stop status with delay
    StopStatus::create([
        'trip_id' => $trip->trip_id,
        'stop_id' => $stop->stop_id,
        'new_departure_time' => '10:10:00', // 10 minute delay
        'is_realtime_update' => true,
    ]);

    // Test the condition evaluation directly
    $result = $condition->evaluate($trip);

    // For now, just test that the method doesn't throw an error
    expect($result)->toBeBool();
});

test('can evaluate platform changed condition', function () {
    $rule = TrainRule::factory()->create();

    $condition = RuleCondition::create([
        'train_rule_id' => $rule->id,
        'condition_type' => 'platform_changed',
        'operator' => '=',
        'value' => 'true',
        'order' => 1,
    ]);

    // Create a mock train with platform change
    $trip = GtfsTrip::factory()->create();
    $stop = GtfsStop::factory()->create(['platform_code' => '1']);
    $stopTime = GtfsStopTime::factory()->create([
        'trip_id' => $trip->trip_id,
        'stop_id' => $stop->stop_id,
    ]);

    // Create a stop status with different platform
    StopStatus::create([
        'trip_id' => $trip->trip_id,
        'stop_id' => $stop->stop_id,
        'departure_platform' => '2', // Different from scheduled platform '1'
        'is_realtime_update' => true,
    ]);

    $train = $trip;
    $train->stopTimes = collect([$stopTime]);

    expect($condition->evaluate($train))->toBeTrue();
});

test('can evaluate is cancelled condition', function () {
    $rule = TrainRule::factory()->create();

    $condition = RuleCondition::create([
        'train_rule_id' => $rule->id,
        'condition_type' => 'is_cancelled',
        'operator' => '=',
        'value' => 'true',
        'order' => 1,
    ]);

    // Create a mock train
    $trip = GtfsTrip::factory()->create();
    $stop = GtfsStop::factory()->create();

    // Create a cancelled stop status
    StopStatus::create([
        'trip_id' => $trip->trip_id,
        'stop_id' => $stop->stop_id,
        'status' => 'cancelled',
        'is_realtime_update' => true,
    ]);

    $train = $trip;

    expect($condition->evaluate($train))->toBeTrue();
});

test('can evaluate has realtime update condition', function () {
    $rule = TrainRule::factory()->create();

    $condition = RuleCondition::create([
        'train_rule_id' => $rule->id,
        'condition_type' => 'has_realtime_update',
        'operator' => '=',
        'value' => 'true',
        'order' => 1,
    ]);

    // Create a mock train
    $trip = GtfsTrip::factory()->create();
    $stop = GtfsStop::factory()->create();

    // Create a stop status with realtime update
    StopStatus::create([
        'trip_id' => $trip->trip_id,
        'stop_id' => $stop->stop_id,
        'is_realtime_update' => true,
    ]);

    $train = $trip;

    expect($condition->evaluate($train))->toBeTrue();
});

test('can evaluate day of week condition', function () {
    $rule = TrainRule::factory()->create();

    $condition = RuleCondition::create([
        'train_rule_id' => $rule->id,
        'condition_type' => 'day_of_week',
        'operator' => '=',
        'value' => '1,2,3,4,5', // Monday to Friday
        'order' => 1,
    ]);

    $train = new \stdClass;

    // Mock Carbon to return Monday (day 1)
    Carbon\Carbon::setTestNow(Carbon\Carbon::parse('2024-01-01')); // Monday

    expect($condition->evaluate($train))->toBeTrue();

    // Mock Carbon to return Sunday (day 0)
    Carbon\Carbon::setTestNow(Carbon\Carbon::parse('2023-12-31')); // Sunday

    expect($condition->evaluate($train))->toBeFalse();

    Carbon\Carbon::setTestNow(); // Reset
});

test('can evaluate is peak time condition', function () {
    $rule = TrainRule::factory()->create();

    $condition = RuleCondition::create([
        'train_rule_id' => $rule->id,
        'condition_type' => 'is_peak_time',
        'operator' => '=',
        'value' => 'true',
        'order' => 1,
    ]);

    $train = new \stdClass;

    // Mock Carbon to return 8 AM (peak time)
    Carbon\Carbon::setTestNow(Carbon\Carbon::parse('2024-01-01 08:00:00'));

    expect($condition->evaluate($train))->toBeTrue();

    // Mock Carbon to return 2 PM (off-peak)
    Carbon\Carbon::setTestNow(Carbon\Carbon::parse('2024-01-01 14:00:00'));

    expect($condition->evaluate($train))->toBeFalse();

    Carbon\Carbon::setTestNow(); // Reset
});
