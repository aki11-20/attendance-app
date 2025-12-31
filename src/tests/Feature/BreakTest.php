<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Carbon\Carbon;

class BreakTest extends TestCase
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

    public function test_break_start_changes_status_to_on_break_and_shows_break_end_button()
    {
        $user = $this->createVerifiedUser();
        
        $this->clockIn($user, Carbon::create(2025, 12, 25, 9, 0, 0));

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('出勤中');
        $response->assertSee('休憩入');

        Carbon::setTestNow(Carbon::create(2025, 12, 25, 12, 0, 0));
        $this->actingAs($user)->post('/attendance/break-start')->assertStatus(302);

        $response2 = $this->actingAs($user)->get('/attendance');
        $response2->assertStatus(200);
        $response2->assertSee('休憩中');
        $response2->assertSee('休憩戻');
        $response2->assertDontSee('休憩入');
    }

    public function test_break_end_changes_status_back_to_working_and_shows_break_start_button_again() {
        $user = $this->createVerifiedUser();

        $this->clockIn($user, Carbon::create(2025, 12, 25, 9, 0, 0));
        Carbon::setTestNow(Carbon::create(2025, 12, 25, 12, 0, 0));
        $this->actingAs($user)->post('/attendance/break-start')->assertStatus(302);

        Carbon::setTestNow(Carbon::create(2025, 12, 25, 12, 30, 0));
        $this->actingAs($user)->post('/attendance/break-end')->assertStatus(302);

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('出勤中');
        $response->assertSee('休憩入');
        $response->assertDontSee('休憩戻');
    }

    public function test_can_take_break_multiple_times_in_a_day_break_start_button_reappears() {
        $user = $this->createVerifiedUser();

        $this->clockIn($user, Carbon::create(2025, 12, 25, 9, 0, 0));

        Carbon::setTestNow(Carbon::create(2025, 12, 25, 12, 0, 0));
        $this->actingAs($user)->post('/attendance/break-start')->assertStatus(302);

        Carbon::setTestNow(Carbon::create(2025, 12, 25, 12, 10, 0));
        $this->actingAs($user)->post('/attendance/break-end')->assertStatus(302);

        $this->actingAs($user)->get('/attendance')->assertStatus(200)->assertSee('休憩入');
    }

    public function test_break_end_button_is_shown_again_on_second_break() {
        $user = $this->createVerifiedUser();

        $this->clockIn($user, Carbon::create(2025, 12, 25, 9, 0, 0));

        Carbon::setTestNow(Carbon::create(2025, 12, 25, 12, 0, 0));
        $this->actingAs($user)->post('/attendance/break-start')->assertStatus(302);

        Carbon::setTestNow(Carbon::create(2025, 12, 25, 12, 10, 0));
        $this->actingAs($user)->post('/attendance/break-end')->assertStatus(302);

        $this->actingAs($user)->get('/attendance')->assertStatus(200)->assertSee('出勤中')->assertSee('休憩入');

        Carbon::setTestNow(Carbon::create(2025, 12, 25, 15, 0, 0));
        $this->actingAs($user)->post('/attendance/break-start')->assertStatus(302);

        $this->actingAs($user)->get('/attendance')->assertStatus(200)->assertSee('休憩中')->assertSee('休憩戻');
    }

    public function test_break_time_is_visible_in_attendance_list() {
        $user = $this->createVerifiedUser();

        $this->clockIn($user, Carbon::create(2025, 12, 25, 9, 0, 0));

        Carbon::setTestNow(Carbon::create(2025, 12, 25, 12, 0, 0));
        $this->actingAs($user)->post('/attendance/break-start')->assertStatus(302);

        Carbon::setTestNow(Carbon::create(2025, 12, 25, 12, 30, 0));
        $this->actingAs($user)->post('/attendance/break-end')->assertStatus(302);

        $this->actingAs($user)->get('/attendance/list')->assertStatus(200)->assertSee('0:30');
    }
}
