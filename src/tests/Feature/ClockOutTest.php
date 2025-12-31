<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Carbon\Carbon;

class ClockOutTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     *
     * @return void
     */

    private function createVerifiedUser(): User {
        return User::factory()->create([
            'email_verified_at' => now(),
        ]);
    }

    private function clockIn(User $user, Carbon $now) {
        Carbon::setTestNow($now);
        $this->actingAs($user)->post('/attendance/clock-in')->assertStatus(302);
    }

    public function test_clock_out_button_works_and_status_changes_to_done()
    {
        $user = $this->createVerifiedUser();
        $this->clockIn($user, Carbon::create(2025, 12, 25, 9, 0, 0));

        $this->actingAs($user)->get('/attendance')->assertStatus(200)->assertSee('退勤');

        Carbon::setTestNow(Carbon::create(2025, 12, 25, 18, 0, 0));
        $this->actingAs($user)->post('/attendance/clock-out')->assertStatus(302);

        $this->actingAs($user)->get('/attendance')->assertStatus(200)->assertSee('退勤済')->assertSee('お疲れ様でした。')->assertDontSee('attendance-end-btn');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'work_date' => Carbon::create(2025, 12, 25)->toDateString(),
        ]);
    }

    public function test_clock_out_time_is_visible_in_attendance_list() {
        $user = $this->createVerifiedUser();

        $this->clockIn($user, Carbon::create(2025, 12, 25, 9, 0, 0));

        Carbon::setTestNow(Carbon::create(2025, 12, 25, 18, 0, 0));
        $this->actingAs($user)->post('/attendance/clock-out')->assertStatus(302);

        $this->actingAs($user)->get('/attendance/list')->assertStatus(200)->assertSee('18:00');
    }
}
