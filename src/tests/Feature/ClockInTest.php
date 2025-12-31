<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Carbon\Carbon;

class ClockInTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_clock_in_button_words_and_status_changes()
    {
        Carbon::setTestNow(Carbon::create(2025, 12, 25, 9, 0, 0));

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)->get('/attendance')->assertStatus(200)->assertSee('出勤');

        $this->actingAs($user)->post('/attendance/clock-in')->assertRedirect('/attendance');

        $this->actingAs($user)->get('/attendance')->assertStatus(200)->assertSee('出勤中')->assertDontSee('>出勤<');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'work_date' => Carbon::today()->toDateString(),
            'clock_out' => null,
        ]);
    }

    public function test_clock_in_button_is_not_shown_when_already_clocked_out() {
        Carbon::setTestNow(Carbon::create(2025, 12, 25, 18, 0, 0));

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        \DB::table('attendances')->insert([
            'user_id' => $user->id,
            'work_date' => Carbon::today()->toDateString(),
            'clock_in' => Carbon::create(2025, 12, 25, 9, 0, 0),
            'clock_out' => Carbon::create(2025, 12, 25, 18, 0, 0),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)->get('/attendance')->assertStatus(200)->assertDontSee('>出勤<');
    }

    public function test_clock_in_time_is_visible_in_attendance_list() {
        Carbon::setTestNow(Carbon::create(2025, 12, 25, 9, 0, 0));

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)->post('/attendance/clock-in')->assertRedirect('/attendance');

        $this->actingAs($user)->get('/attendance/list')->assertStatus(200)->assertSee('09:00');
    }
}
