<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceListTest extends TestCase
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

    public function test_user_attendance_list_shows_all_own_attendances_in_month()
    {
        Carbon::setTestNow(Carbon::create(2025, 12, 25, 9, 0, 0));
        $user = $this->createVerifiedUser();

        $id1 = DB::table('attendances')->insertGetId([

            'user_id' => $user->id,
            'work_date' => '2025-12-05',
            'clock_in' => '2025-12-05 09:00:00',
            'clock_out' => '2025-12-05 18:00:00',
            'total_break_minutes' => 60,
            'total_work_minutes' => 480,
            'status' => Attendance::STATUS_DONE,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
            
        $id2 = DB::table('attendances')->insertGetId([
            'user_id' => $user->id,
            'work_date' => '2025-12-25',
            'clock_in' => '2025-12-25 09:00:00',
            'clock_out' => '2025-12-25 18:00:00',
            'total_break_minutes' => 30,
            'total_work_minutes' => 510,
            'status' => Attendance::STATUS_DONE,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('breaks')->insert([
            [
                'attendance_id' => $id1,
                'break_no' => 1,
                'break_start' => '2025-12-05 12:00:00',
                'break_end' => '2025-12-05 13:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'attendance_id' => $id2,
                'break_no' => 1,
                'break_start' => '2025-12-25 12:00:00',
                'break_end' => '2025-12-25 12:30:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->actingAs($user)->get('/attendance/list?month=2025-12');
        $response->assertStatus(200);

        $response->assertSee('09:00');
        $response->assertSee('18:00');

        $response->assertSee('1:00');
        $response->assertSee('0:30');
    }

    public function test_attendance_list_shows_current_month_on_open() {
        Carbon::setTestNow(Carbon::create(2025, 12, 25, 9, 0, 0));
        $user = $this->createVerifiedUser();

        $this->actingAs($user)->get('/attendance/list')->assertStatus(200)->assertSee('2025/12');
    }

    public function test_prev_month_button_points_to_previous_month() {
        Carbon::setTestNow(Carbon::create(2025, 12, 25, 9, 0, 0));
        $user = $this->createVerifiedUser();

        $this->actingAs($user)->get('/attendance/list')->assertStatus(200)->assertSee('month=2025-11');
    }

    public function test_next_month_button_points_to_next_month() {
        Carbon::setTestNow(Carbon::create(2025, 12, 25, 9, 0, 0));
        $user = $this->createVerifiedUser();

        $this->actingAs($user)->get('/attendance/list')->assertStatus(200)->assertSee('month=2026-01');
    }

    public function test_detail_link_navigates_to_attendance_detail() {
        Carbon::setTestNow(Carbon::create(2025, 12, 25, 9, 0, 0));
        $user = $this->createVerifiedUser();

        $attendanceId = DB::table('attendances')->insertGetId([
            'user_id' => $user->id,
            'work_date' => '2025-12-25',
            'clock_in' => '2025-12-25 09:00:00',
            'clock_out' => '2025-12-25 18:00:00',
            'total_break_minutes' => 30,
            'total_work_minutes' => 510,
            'status' => Attendance::STATUS_DONE,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $list = $this->actingAs($user)->get('/attendance/list?month=2025-12');
        $list->assertStatus(200);
        $list->assertSee('attendance/detail/' . $attendanceId);

        $this->actingAs($user)->get('/attendance/detail/' . $attendanceId)->assertStatus(200)->assertSee('勤怠詳細');
    }
}
