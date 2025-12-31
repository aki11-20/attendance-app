<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class AdminUserInfoTest extends TestCase
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

    private function staffListUrl(): string {
        return route('admin.staff.list');
    }

    private function staffMonthlyUrl(int $userId, ?string $month = null): string {
        $url = route('admin.attendance.staff', ['id' => $userId]);
        if ($month) {
            $url .= '?month=' . $month;
        }
        return $url;
    }

    private function adminAttendanceDetailUrl(int $attendanceId): string {
        return route('admin.attendance.detail', ['id' => $attendanceId]);
    }

    public function test_admin_can_see_all_users_name_and_email_on_staff_list()
    {
        $admin = $this->createAdminUser();

        $user1 = $this->createVerifiedUser(['name' => '一般ユーザー1', 'email' => 'user1@example.com']);
        $user2 = $this->createVerifiedUser(['name' => '一般ユーザー2', 'email' => 'user2@example.com']);

        $response = $this->actingAs($admin)->get($this->staffListUrl());

        $response->assertStatus(200);

        $response->assertSee('一般ユーザー1')->assertSee('user1@example.com');
        $response->assertSee('一般ユーザー2')->assertSee('user2@example.com');
    }

    public function test_admin_can_see_selected_users_attendance_list() {
        Carbon::setTestNow(Carbon::create(2025, 12, 25, 10, 0, 0));

        $admin = $this->createAdminUser();
        $user = $this->createVerifiedUser(['name' => '一覧テスト', 'email' => 'list@example.com']);

        $attendanceId1 = $this->seedAttendance($user, '2025-12-05', '09:00', '18:00', 0, 480);
        $attendanceId2 = $this->seedAttendance($user, '2025-12-25', '09:00', '18:00', 30, 510);

        $response = $this->actingAs($admin)->get($this->staffMonthlyUrl($user->id, '2025-12'));

        $response->assertStatus(200);

        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('詳細');

        $this->actingAs($admin)->get($this->adminAttendanceDetailUrl($attendanceId1))->assertStatus(200);
        $this->actingAs($admin)->get($this->adminAttendanceDetailUrl($attendanceId2))->assertStatus(200);
    }

    public function test_admin_prev_month_and_next_month_navigation_changes_month() {
        Carbon::setTestNow(Carbon::create(2025, 12, 25, 10, 0, 0));

        $admin = $this->createAdminUser();
        $user = $this->createVerifiedUser();

        $this->seedAttendance($user, '2025-11-10', '09:00', '18:00', 0, 480);
        $this->seedAttendance($user, '2025-12-05', '09:00', '18:00', 0, 480);
        $this->seedAttendance($user, '2026-01-10', '09:00', '18:00', 0, 480);

        $responseDec = $this->actingAs($admin)->get($this->staffMonthlyUrl($user->id, '2025-12'));
        $responseDec->assertStatus(200)->assertSee('2025/12');

        $responseNov = $this->actingAs($admin)->get($this->staffMonthlyUrl($user->id, '2025-11'));
        $responseNov->assertStatus(200)->assertSee('2025/11');

        $responseJan = $this->actingAs($admin)->get($this->staffMonthlyUrl($user->id, '2026-01'));
        $responseJan->assertStatus(200)->assertSee('2026/01');
    }
}
