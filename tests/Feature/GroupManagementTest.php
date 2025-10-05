<?php

use App\Models\Group;
use App\Models\User;
use App\Models\Zone;
use Livewire\Volt\Volt;

test('group management component can be rendered', function () {
    $user = User::factory()->create(['role' => 'admin']);

    $this->actingAs($user)
        ->get('/settings/admin')
        ->assertStatus(200);
});

test('can create group with zones', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $zone1 = Zone::create(['item_id' => 'Zones', 'value' => 'Terminal']);
    $zone2 = Zone::create(['item_id' => 'Zones', 'value' => 'Lounge']);

    $this->actingAs($user);

    Volt::test('group-management')
        ->set('name', 'Test Group')
        ->set('description', 'Test Description')
        ->set('selectedZones', [$zone1->id, $zone2->id])
        ->set('active', true)
        ->call('saveGroup')
        ->assertHasNoErrors();

    $group = Group::where('name', 'Test Group')->first();
    expect($group)->not->toBeNull();
    expect($group->zones)->toHaveCount(2);
    expect($group->zones->pluck('value')->toArray())->toContain('Terminal', 'Lounge');
});

test('can edit group and update zones', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $group = Group::factory()->create(['name' => 'Original Group']);
    $zone1 = Zone::create(['item_id' => 'Zones', 'value' => 'Terminal']);
    $zone2 = Zone::create(['item_id' => 'Zones', 'value' => 'Lounge']);

    // Initially attach zone1
    $group->zones()->attach($zone1);

    $this->actingAs($user);

    Volt::test('group-management')
        ->call('editGroup', $group->id)
        ->assertSet('name', 'Original Group')
        ->assertSet('selectedZones', [$zone1->id])
        ->set('selectedZones', [$zone2->id]) // Change to zone2
        ->call('saveGroup')
        ->assertHasNoErrors();

    $group->refresh();
    expect($group->zones)->toHaveCount(1);
    expect($group->zones->first()->value)->toBe('Lounge');
});

test('can clear zone selection', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $zone1 = Zone::create(['item_id' => 'Zones', 'value' => 'Terminal']);

    $this->actingAs($user);

    Volt::test('group-management')
        ->set('selectedZones', [$zone1->id])
        ->assertSet('selectedZones', [$zone1->id])
        ->call('clearZoneSelection')
        ->assertSet('selectedZones', []);
});

test('validation works for zones', function () {
    $user = User::factory()->create(['role' => 'admin']);

    $this->actingAs($user);

    Volt::test('group-management')
        ->set('name', 'Test Group')
        ->set('selectedZones', [99999]) // Invalid zone ID
        ->call('saveGroup')
        ->assertHasErrors(['selectedZones.0' => 'exists']);
});

test('group displays zones in table', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $group = Group::factory()->create(['name' => 'Test Group']);
    $zone1 = Zone::create(['item_id' => 'Zones', 'value' => 'Terminal']);
    $zone2 = Zone::create(['item_id' => 'Zones', 'value' => 'Lounge']);

    $group->zones()->attach([$zone1->id, $zone2->id]);

    $this->actingAs($user);

    Volt::test('group-management')
        ->assertSee('Test Group')
        ->assertSee('2 zones');
});
