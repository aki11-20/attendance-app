<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class AttendanceCorrectionFlowTest extends TestCase
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
        return DB::table('attendances')->insertGetId([
            'user_id' => $user->id,
            'work_date' => $workDate,
            'clock_in' => Carbon::createFromFormat('H:i', $in),
            'clock_out' => Carbon::createFromFormat('H:i', $out),
            'total_break_minutes' => $break,
            'total_work_minutes' => $total,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (int) DB::table('attendances')->where('user_id', $user->id)->where('work_date', $workDate)->orderByDesc('id')->value('id');
    }

    private function detailUrl(int $attendanceId): string {
        return "/attendance/detail/{$attendanceId}";
    }

    private function correctionStoreUrl(int $attendanceId): string {
        return "/attendance/{$attendanceId}/correction-request";
    }

    public function test_shows_error_when_clock_in_is_after_clock_out()
    {
        $user = $this->createVerifiedUser();
        $attendanceId = $this->seedAttendance($user, '2025-12-05', '09:00', '18:00');

        $this->actingAs($user)->followingRedirects()->from($this->detailUrl($attendanceId))->post($this->correctionStoreUrl($attendanceId), [
            'clock_in' => '19:00',
            'clock_out' => '18:00',
            'comment' => 'テスト'
        ])->assertStatus(200)->assertSee('出勤時間もしくは退勤時間が不適切な値です');
    }

    public function test_shows_error_when_break_start_is_after_clock_out() {
        $user = $this->createVerifiedUser();
        $attendanceId = $this->seedAttendance($user, '2025-12-05', '09:00', '18:00');

        $this->actingAs($user)->followingRedirects()->from($this->detailUrl($attendanceId))->post($this->correctionStoreUrl($attendanceId), [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'breaks' => [['start' => '19:10', 'end' => '19:20']],
            'comment' => 'テスト'
        ])->assertStatus(200)->assertSee('休憩時間が不適切な値です');
    }

    public function test_shows_error_when_break_end_is_after_clock_out() {
        $user = $this->createVerifiedUser();
        $attendanceId = $this->seedAttendance($user, '2025-12-05', '09:00', '18:00');

        $this->actingAs($user)->followingRedirects()->from($this->detailUrl($attendanceId))->post($this->correctionStoreUrl($attendanceId), [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'breaks' => [['start' => '17:00', 'end' => '19:40']],
            'comment' => 'テスト'
        ])->assertStatus(200)->assertSee('休憩時間もしくは退勤時間が不適切な値です');
    }

    public function test_shows_error_when_comment_is_missing() {
        $user = $this->createVerifiedUser();
        $attendanceId = $this->seedAttendance($user, '2025-12-05', '09:00', '18:00');

        $this->actingAs($user)->followingRedirects()->from($this->detailUrl($attendanceId))->post($this->correctionStoreUrl($attendanceId), [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'breaks' => [['start' => '12:00', 'end' => '12:30']],
        ])->assertStatus(200)->assertSee('備考を記入してください');
    }

    public function test_correction_request_is_created_on_valid_update() {
        $user = $this->createVerifiedUser();
        $attendanceId = $this->seedAttendance($user, '2025-12-05', '09:00', '18:00');

        $this->actingAs($user)->from($this->detailUrl($attendanceId))->post($this->correctionStoreUrl($attendanceId), [
            'clock_in' => '08:50',
            'clock_out' => '18:10',
            'breaks' => [['start' => '12:00', 'end' => '12:30']],
            'comment' => 'テスト'
        ])->assertStatus(302);

        $this->assertDatabaseHas('correction_requests', ['attendance_id' => $attendanceId]);
    }

    public function test_user_requests_are_listed_in_pending_page() {
        $user = $this->createVerifiedUser();
        $attendanceId = $this->seedAttendance($user, '2025-12-05', '09:00', '18:00');

        $this->actingAs($user)->from($this->detailUrl($attendanceId))->post($this->correctionStoreUrl($attendanceId), [
            'clock_in' => '09:00',
            'clock_out' => '17:00',
            'breaks' => [['start' => '12:00', 'end' => '12:30']],
            'comment' => 'テスト'
        ])->assertStatus(302);

        $this->actingAs($user)->get('/stamp_correction_request/list')->assertStatus(200)->assertSee('承認待ち');
    }

    public function test_admin_approved_requests_are_listed_in_done_page() {
        $admin = $this->createAdminUser();

        $user = $this->createVerifiedUser();
        $attendanceId = $this->seedAttendance($user, '2025-12-05', '09:00', '18:00');

        $this->actingAs($user)->from($this->detailUrl($attendanceId))->post($this->correctionStoreUrl($attendanceId), [
            'clock_in' => '09:00',
            'clock_out' => '17:00',
            'breaks' => [['start' => '12:00', 'end' => '12:30']],
            'comment' => 'テスト',
        ])->assertStatus(302);

        $requestId = (int) DB::table('correction_requests')->where('attendance_id', $attendanceId)->orderByDesc('id')->value('id');

        $this->actingAs($admin)->post(route('admin.request.approve', ['id' => $requestId]), ['comment' => 'OK'])->assertStatus(302);
        
        $this->actingAs($admin)->get('/stamp_correction_request/list?status=approved')->assertStatus(200)->assertSee('承認済み');
    }

    public function test_detail_link_in_requests_page_navigates_to_attendance_detail() {
        $user = $this->createVerifiedUser();
        $attendanceId = $this->seedAttendance($user, now()->toDateString(), '09:00', '18:00');

        $this->actingAs($user)->get('/stamp_correction_request/list')->assertStatus(200)->assertSee('詳細');

        $this->actingAs($user)->get($this->detailUrl($attendanceId))->assertStatus(200);
    }
}
