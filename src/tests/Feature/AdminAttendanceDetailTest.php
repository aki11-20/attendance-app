<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class AdminAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     *
     * @return void
     */

    private function createVerifiedUser(array $attrs = []): User {
        return User::factory()->create(array_merge([
            'email_verified_at' => now(),
        ], $attrs));
    }

    private function createAdminUser(): User {
        $attrs = [
            'email_verified_at' => now(),
        ];

        if (Schema::hasColumn('users', 'role')) {
            $attrs['role'] = defined(User::class . '::ROLE_ADMIN') ? User::ROLE_ADMIN : 'admin';
        }

        if (Schema::hasColumn('users', 'is_admin')) {
            $attrs['is_admin'] = 1;
        }

        if (Schema::hasColumn('users', 'admin_flg')) {
            $attrs['admin_flg'] = 1;
        }

        if (Schema::hasColumn('users', 'type')) {
            $attrs['type'] = 'admin';
        }

        if (Schema::hasColumn('users', 'user_type')) {
            $attrs['user_type'] = 'admin';
        }

        return User::factory()->create($attrs);
    }

    private function seedAttendance(User $user, string $workDate, string $in, string $out, int $break = 0, int $total = 0): int {
        DB::table('attendances')->insert([
            'user_id' => $user->id,
            'work_date' => $workDate,
            'clock_in' => Carbon::parse($workDate . ' ' . $in . ':00'),
            'clock_out' => Carbon::parse($workDate . ' ' . $out . ':00'),
            'total_break_minutes' => $break,
            'total_work_minutes' => $total,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (int) DB::table('attendances')->where('user_id', $user->id)->where('work_date', $workDate)->orderByDesc('id')->value('id');
    }

    private function seedBreak(int $attendanceId, string $workDate, string $start, string $end, int $no = 1): void {
        DB::table('breaks')->insert([
            'attendance_id' => $attendanceId,
            'break_no' => $no,
            'break_start' => Carbon::parse($workDate . ' ' . $start . ':00'),
            'break_end' => Carbon::parse($workDate . ' ' . $end . ':00'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function adminDetailUrl(int $attendanceId): string {
        return "/admin/attendance/{$attendanceId}";
    }

    private function adminUpdateUrl(int $attendanceId): string {
        return route('admin.attendance.update', ['id' => $attendanceId]);
    }

    public function test_admin_detail_shows_selected_attendance_data()
    {
        Carbon::setTestNow(Carbon::create(2025, 12, 25, 10, 0, 0));

        $admin = $this->createAdminUser();
        $user = $this->createVerifiedUser(['name' => 'テスト太郎']);

        $attendanceId = $this->seedAttendance($user, '2025-12-05', '09:00', '18:00', 30, 510);
        $this->seedBreak($attendanceId, '2025-12-05', '12:00', '12:30', 1);


        $response = $this->actingAs($admin)->get($this->adminDetailUrl($attendanceId));
        $response->assertStatus(200);

        $response->assertSee('テスト太郎');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('12:00');
        $response->assertSee('12:30');
    }

    public function test_admin_shows_error_when_clock_in_is_sfter_clock_out() {
        $admin = $this->createAdminUser();
        $user = $this->createVerifiedUser();

        $attendanceId = $this->seedAttendance($user, '2025-12-05', '09:00', '18:00');

        $response = $this->actingAs($admin)->followingRedirects()->from($this->adminDetailUrl($attendanceId))->post($this->adminUpdateUrl($attendanceId), [
            'clock_in' => '19:00',
            'clock_out' => '18:00',
            'comment' => 'テスト',
        ]);

        $response->assertStatus(200)->assertSee('出勤時間もしくは退勤時間が不適切な値です');
    }

    public function test_admin_shows_error_when_break_start_is_after_clock_out() {
        $admin = $this->createAdminUser();
        $user = $this->createVerifiedUser();

        $attendanceId = $this->seedAttendance($user, '2025-12-05', '09:00', '18:00');

        $response = $this->actingAs($admin)->followingRedirects()->from($this->adminDetailUrl($attendanceId))->post($this->adminUpdateUrl($attendanceId), [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'breaks' => [['start' => '19:10', 'end' => '19:20']],
            'comment' => 'テスト',
        ]);

        $response->assertStatus(200)->assertSee('休憩時間が不適切な値です');
    }

    public function test_admin_shows_error_when_break_end_after_clock_out() {
        $admin = $this->createAdminUser();
        $user = $this->createVerifiedUser();

        $attendanceId = $this->seedAttendance($user, '2025-12-05', '09:00', '18:00');

        $response = $this->actingAs($admin)->followingRedirects()->from($this->adminDetailUrl($attendanceId))->post($this->adminUpdateUrl($attendanceId), [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'breaks' => [['start' => '17:00', 'end' => '19:40']],
            'comment' => 'テスト',
        ]);

        $response->assertStatus(200)->assertSee('休憩時間もしくは退勤時間が不適切な値です');
    }

    public function test_admin_shows_error_when_comment_is_missing() {
        $admin = $this->createAdminUser();
        $user = $this->createVerifiedUser();

        $attendanceId = $this->seedAttendance($user, '2025-12-05', '09:00', '18:00');

        $response = $this->actingAs($admin)->followingRedirects()->from($this->adminDetailUrl($attendanceId))->post($this->adminUpdateUrl($attendanceId), [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'breaks' => [['start' => '12:00', 'end' => '12:30']],
        ]);

        $response->assertStatus(200)->assertSee('備考を記入してください');
    }
}
