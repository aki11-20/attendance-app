<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AttendanceDetailTest extends TestCase
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

    private function seedAttendanceWithBreaks(User $user): int {
        $attendanceId = DB::table('attendances')->insertGetId([
            'user_id' => $user->id,
            'work_date' => '2025-12-25',
            'clock_in' => '2025-12-25 09:00:00',
            'clock_out' => '2025-12-25 18:00:00',
            'total_break_minutes' => 30,
            'total_work_minutes' => 510,
            'status' => defined(Attendance::class.'::STATUS_DONE') ? Attendance::STATUS_DONE : 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('breaks')->insert([
            'attendance_id' => $attendanceId,
            'break_no' => 1,
            'break_start' => '2025-12-25 12:00:00',
            'break_end' => '2025-12-25 12:30:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $attendanceId;
    }

    public function test_detail_shows_logged_in_user_name()
    {
        $user = $this->createVerifiedUser();
        $attendanceId = $this->seedAttendanceWithBreaks($user);

        $response = $this->actingAs($user)->get("/attendance/detail/{$attendanceId}");
        $response->assertStatus(200);
        $response->assertSee($user->name);
    }

    public function test_detail_shows_selected_date() {
        $user = $this->createVerifiedUser();
        $attendanceId = $this->seedAttendanceWithBreaks($user);

        $response = $this->actingAs($user)->get("/attendance/detail/{$attendanceId}");
        $response->assertStatus(200);

        $text = preg_replace('/\s+/u', ' ', strip_tags($response->getContent()));

        $this->assertTrue(preg_match('/2025\s*年.*?12\s*月\s*25\s*日/u', $text) === 1, '勤怠詳細に選択日付が表示されていません。');

    }

    public function test_detail_shows_clock_in_and_out_time() {
        $user = $this->createVerifiedUser();
        $attendanceId = $this->seedAttendanceWithBreaks($user);

        $response = $this->actingAs($user)->get("/attendance/detail/{$attendanceId}");
        $response->assertStatus(200);

        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    public function test_detail_shows_break_times() {
        $user = $this->createVerifiedUser();
        $attendanceId = $this->seedAttendanceWithBreaks($user);

        $response = $this->actingAs($user)->get("/attendance/detail/{$attendanceId}");
        $response->assertStatus(200);

        $response->assertSee('12:00');
        $response->assertSee('12:30');
    }
}
