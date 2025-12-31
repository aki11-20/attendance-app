<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Carbon\Carbon;

class AttendanceStatusTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     *
     * @return void
     */

    private function loginverifiedUser(): User {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'role' => User::ROLE_USER,
        ]);

        $this->actingAs($user);

        return $user;
    }

    public function test_status_is_out_of_work_when_no_clock_in()
    {
        Carbon::setTestNow(Carbon::create(2025, 12, 25, 9, 0, 0));

        $this->loginVerifiedUser();

        $this->get('/attendance')->assertOk()->assertSee('勤務外');
    }

    public function test_status_is_working_after_clock_in() {
        Carbon::setTestNow(Carbon::create(2025, 12, 25, 9, 0, 0));

        $this->loginVerifiedUser();

        $this->post('/attendance/clock-in')->assertRedirect('/attendance');

        $this->get('/attendance')->assertOk()->assertSee('出勤中');
    }

    public function test_status_is_on_break_after_break_start() {
        Carbon::setTestNow(Carbon::create(2025, 12, 25, 9, 0, 0));

        $this->loginVerifiedUser();

        $this->post('/attendance/clock-in')->assertRedirect('/attendance');
        $this->post('/attendance/break-start')->assertRedirect('/attendance');

        $this->get('/attendance')->assertOk()->assertSee('休憩中');
    }

    public function test_status_is_clocked_out_after_clock_out() {
        Carbon::setTestNow(Carbon::create(2025, 12, 25, 9, 0, 0));

        $this->loginVerifiedUser();

        $this->post('/attendance/clock-in')->assertRedirect('/attendance');
        $this->post('/attendance/clock-out')->assertRedirect('/attendance');
        $this->get('/attendance')->assertOk()->assertSee('退勤済');
    }
}
