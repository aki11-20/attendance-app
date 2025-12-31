<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\CorrectionRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class AdminCorrectionFlowTest extends TestCase
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

    private function seedAttendance(User $user, string $workDate, string $in, string $out, int $breaks = 0, int $total = 0, int $status = 1): int {
        DB::table('attendances')->insert([
            'user_id' => $user->id,
            'work_date' => $workDate,
            'clock_in' => Carbon::parse($workDate . ' ' . $in . ':00'),
            'clock_out' => Carbon::parse($workDate . ' ' . $out . ':00'),
            'total_break_minutes' => $breaks,
            'total_work_minutes' => $total,
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (int) DB::table('attendances')->where('user_id', $user->id)->where('work_date', $workDate)->orderByDesc('id')->value('id');
    }

    private function createCorrectionRequestByUser(User $user, int $attendanceId, array $payload): int {
        $response = $this->actingAs($user)->post(route('stamp_requests.store', ['attendance' => $attendanceId]), $payload);

        $response->assertStatus(302);

        return (int) DB::table('correction_requests')->where('attendance_id', $attendanceId)->orderByDesc('id')->value('id');
    }

    public function test_pending_tab_shows_all_users_pending_requests()
    {
        Carbon::setTestNow(Carbon::create(2025, 12, 25, 10, 0, 0));

        $admin = $this->createAdminUser();

        $user1 = $this->createVerifiedUser(['name' => '一般ユーザー1', 'email' => 'user1@example.com']);
        $user2 = $this->createVerifiedUser(['name' => '一般ユーザー2', 'email' => 'user2@example.com']);

        $attendanceId1 = $this->seedAttendance($user1, '2025-12-05', '09:00', '18:00', 0, 480);
        $attendanceId2 = $this->seedAttendance($user2, '2025-12-05', '09:00', '18:00', 0, 480);

        $requestId1 = $this->createCorrectionRequestByUser($user1, $attendanceId1, [
            'clock_in' => '08:50',
            'clock_out' => '18:10',
            'breaks' => [['start' => '12:00', 'end' => '12:30']],
            'comment' => '申請1',
        ]);

        $requestId2 = $this->createCorrectionRequestByUser($user2, $attendanceId2, [
            'clock_in' => '09:10',
            'clock_out' => '17:50',
            'breaks' => [['start' => '12:10', 'end' => '12:40']],
            'comment' => '申請2',
        ]);

        $response = $this->actingAs($admin)->get('/stamp_correction_request/list');
        $response->assertStatus(200);

        $response->assertSee('承認待ち');
        $response->assertSee('一般ユーザー1');
        $response->assertSee('一般ユーザー2');

        if (defined(CorrectionRequest::class . '::STATUS_PENDING')) {
            $this->assertDatabaseCount('correction_requests', 2);
            $this->assertDatabaseHas('correction_requests', ['id' => $requestId1, 'attendance_id' => $attendanceId1, 'status' => CorrectionRequest::STATUS_PENDING]);
            $this->assertDatabaseHas('correction_requests', ['id' => $requestId2, 'attendance_id' => $attendanceId2, 'status' => CorrectionRequest::STATUS_PENDING]);
        }
    }

    public function test_approved_tab_shows_all_approved_requests() {
        Carbon::setTestNow(Carbon::create(2025, 12, 25, 10, 0, 0));

        $admin = $this->createAdminUser();
        $user = $this->createVerifiedUser(['name' => '承認テストユーザー', 'email' => 'approve@example.com']);

        $attendanceId = $this->seedAttendance($user, '2025-12-05', '09:00', '18:00', 0, 480);

        $requestId = $this->createCorrectionRequestByUser($user, $attendanceId, [
            'clock_in' => '08:55',
            'clock_out' => '18:05',
            'breaks' => [['start' => '12:00', 'end' => '12:30']],
            'comment' => '承認お願いします',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.request.approve', ['id' => $requestId]), ['comment' => 'OK']);
        $response->assertStatus(302);

        $approvedList = $this->actingAs($admin)->get('/stamp_correction_request/list?tab=approved');
        $approvedList->assertStatus(200)->assertSee('承認済み')->assertSee('承認テストユーザー');
    }

    public function test_admin_can_view_request_detail_and_requested_values_are_visible() {
        Carbon::setTestNow(Carbon::create(2025, 12, 25, 10, 0, 0));

        $admin = $this->createAdminUser();
        $user = $this->createVerifiedUser(['name' => '詳細表示ユーザー', 'email' => 'detail@example.com']);

        $attendanceId = $this->seedAttendance($user, '2025-12-05', '09:00', '18:00', 0, 480);

        $requestId = $this->createCorrectionRequestByUser($user, $attendanceId, [
            'clock_in' => '08:40',
            'clock_out' => '18:20',
            'breaks' => [['start' => '12:05', 'end' => '12:35']],
            'comment' => '詳細見る',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.request.show', ['id' => $requestId]));
        $response->assertStatus(200);

        $response->assertSee('詳細表示ユーザー');
        $response->assertSee('詳細見る');
        $response->assertSee('承認');
    }

    public function test_approving_request_updates_attendance_and_marks_request_as_approved() {
        Carbon::setTestNow(Carbon::create(2025, 12, 25, 10, 0, 0));

        $admin = $this->createAdminUser();
        $user = $this->createVerifiedUser(['name' => '変更反映ユーザー', 'email' => 'apply@example.com']);

        $workDate = '2025-12-05';
        $attendanceId = $this->seedAttendance($user, $workDate, '09:00', '18:00', 0, 480);

        $requestId = $this->createCorrectionRequestByUser($user, $attendanceId, [
            'clock_in' => '08:30',
            'clock_out' => '18:30',
            'breaks' => [['start' => '12:00', 'end' => '12:30']],
            'comment' => '反映する',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.request.approve', ['id' => $requestId]), ['comment' => '承認OK']);
        $response->assertStatus(302);

        if (defined(CorrectionRequest::class . '::STATUS_APPROVED')) {
            $this->assertDatabaseHas('correction_requests', [
                'id' => $requestId,
                'status' => CorrectionRequest::STATUS_APPROVED,
            ]);
        }

        $attendance = DB::table('attendances')->where('id', $attendanceId)->first();
        $this->assertNotNull($attendance);

        $this->assertSame('08:30:00', Carbon::parse($attendance->clock_in)->format('H:i:s'));
        $this->assertSame('18:30:00', Carbon::parse($attendance->clock_out)->format('H:i:s'));
    }
}
