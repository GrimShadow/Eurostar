<?php

use App\Models\CheckInStatus;
use App\Models\GtfsTrip;
use App\Models\TrainCheckInStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('can create check-in status', function () {
    $user = User::factory()->create(['role' => 'admin']);

    Livewire::actingAs($user)
        ->test(\App\Livewire\CheckInStatuses::class)
        ->set('newStatus', 'Not Started')
        ->set('newColorName', 'Gray')
        ->set('newColorRgb', '128,128,128')
        ->call('save')
        ->assertHasNoErrors();

    expect(CheckInStatus::where('status', 'Not Started')->exists())->toBeTrue();
});

test('can delete check-in status', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $checkInStatus = CheckInStatus::create([
        'status' => 'Pre-check-in',
        'color_name' => 'Yellow',
        'color_rgb' => '255,255,0',
    ]);

    Livewire::actingAs($user)
        ->test(\App\Livewire\CheckInStatuses::class)
        ->call('deleteStatus', $checkInStatus->id)
        ->assertHasNoErrors();

    expect(CheckInStatus::find($checkInStatus->id))->toBeNull();
});

test('check-in status validation works', function () {
    $user = User::factory()->create(['role' => 'admin']);

    Livewire::actingAs($user)
        ->test(\App\Livewire\CheckInStatuses::class)
        ->set('newStatus', '')
        ->set('newColorName', '')
        ->set('newColorRgb', '')
        ->call('save')
        ->assertHasErrors(['newStatus', 'newColorName', 'newColorRgb']);
});

test('check-in status RGB format validation works', function () {
    $user = User::factory()->create(['role' => 'admin']);

    Livewire::actingAs($user)
        ->test(\App\Livewire\CheckInStatuses::class)
        ->set('newStatus', 'Test Status')
        ->set('newColorName', 'Test Color')
        ->set('newColorRgb', 'invalid')
        ->call('save')
        ->assertHasErrors(['newColorRgb']);
});

test('can assign check-in status to train', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $checkInStatus = CheckInStatus::create([
        'status' => 'Check-in Started',
        'color_name' => 'Green',
        'color_rgb' => '0,255,0',
    ]);

    $trip = GtfsTrip::factory()->create([
        'trip_id' => 'test-trip-123',
    ]);

    $trainCheckInStatus = TrainCheckInStatus::create([
        'trip_id' => $trip->trip_id,
        'check_in_status_id' => $checkInStatus->id,
    ]);

    expect($trainCheckInStatus->checkInStatus->status)->toBe('Check-in Started');
    expect($trainCheckInStatus->trip_id)->toBe($trip->trip_id);
});

test('can remove check-in status from train', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $checkInStatus = CheckInStatus::create([
        'status' => 'Check-in Started',
        'color_name' => 'Green',
        'color_rgb' => '0,255,0',
    ]);

    $trip = GtfsTrip::factory()->create([
        'trip_id' => 'test-trip-456',
    ]);

    TrainCheckInStatus::create([
        'trip_id' => $trip->trip_id,
        'check_in_status_id' => $checkInStatus->id,
    ]);

    expect(TrainCheckInStatus::where('trip_id', $trip->trip_id)->exists())->toBeTrue();

    TrainCheckInStatus::where('trip_id', $trip->trip_id)->delete();

    expect(TrainCheckInStatus::where('trip_id', $trip->trip_id)->exists())->toBeFalse();
});

test('check-in statuses are displayed in settings page', function () {
    $user = User::factory()->create(['role' => 'admin']);
    CheckInStatus::create([
        'status' => 'Not Started',
        'color_name' => 'Gray',
        'color_rgb' => '128,128,128',
    ]);

    $response = $this->actingAs($user)->get('/settings');

    $response->assertStatus(200);
    $response->assertSee('Check-in Status Settings');
    $response->assertSeeLivewire(\App\Livewire\CheckInStatuses::class);
});
