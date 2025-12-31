<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Route;
use Carbon\Carbon;

class AdminAttendanceListTest extends TestCase
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
            $attrs['role'] = defined(User::class . '::ROLE_ADMIN') ? User::ROLE_ADMIN :'admin';
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

    private function adminDailyListUrl(?string $dateYmd = null): string {
        $query = $dateYmd ? ['date' => $dateYmd] : [];

        $routeNameCandidates = [
            'admin.attendance.index',
            'admin.attendance.list',
            'admin.attendance.daily',
            'admin.attendances.index',
            'admin.attendances.list',
            'admin.attendances.daily',
        ];

        foreach ($routeNameCandidates as $name) {
            if (Route::has($name)) {
                return $query ? route($name, $query) : route($name);
            }
        }

        $pathCandidates = [
            '/admin/attendance/list',
            '/admin/attendance',
            '/admin/attendances/list',
            '/admin/attendances',
        ];

        $base = $pathCandidates[0];
        if ($query) {
            return $base . '?date=' . $dateYmd;
        }
        return $base;
    }

    public function test_admin_attendance_list_shows_all_users_attendances_of_the_day()
    {
        Carbon::setTestNow(Carbon::create(2025, 12, 25, 10, 0, 0));

        $admin = $this->createAdminUser();
        $user1 = $this->createVerifiedUser();
        $user2 = $this->createVerifiedUser();

        $this->seedAttendance($user1, '2025-12-25', '09:00', '18:00', 30, 510);
        $this->seedAttendance($user2, '2025-12-25', '10:00', '19:00', 60, 480);

        $url = $this->adminDailyListUrl();
        $response = $this->actingAs($admin)->get($url);

        $response->assertStatus(200);

        $response->assertSee($user1->name);
        $response->assertSee('09:00');
        $response->assertSee('18:00');

        $response->assertSee($user2->name);
        $response->assertSee('10:00');
        $response->assertSee('19:00');
    }

    public function test_admin_attendance_list_shows_current_date_on_initial_view() {
        Carbon::setTestNow(Carbon::create(2025, 12, 25, 10, 0, 0));

        $admin = $this->createAdminUser();

        $url = $this->adminDailyListUrl();
        $response = $this->actingAs($admin)->get($url);

        $response->assertStatus(200);

        $response->assertSee('2025');
        $response->assertSee('12');
        $response->assertSee('25');
    }

    public function test_clicking_previous_day_shows_previous_date_attendance() {
        Carbon::setTestNow(Carbon::create(2025, 12, 25, 10, 0, 0));

        $admin = $this->createAdminUser();
        $user = $this->createVerifiedUser();

        $this->seedAttendance($user, '2025-12-24', '09:00', '18:00', 0, 540);

        $url = $this->adminDailyListUrl('2025-12-24');
        $response = $this->actingAs($admin)->get($url);

        $response->assertStatus(200);
        $response->assertSee('2025');
        $response->assertSee('12');
        $response->assertSee('24');

        $response->assertSee($user->name);
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    public function test_clicking_next_day_shows_next_date_attendance() {
        Carbon::setTestNow(Carbon::create(2025, 12, 25, 10, 0, 0));

        $admin = $this->createAdminUser();
        $user = $this->createVerifiedUser();

        $this->seedAttendance($user, '2025-12-26', '10:00', '19:00', 0, 540);

        $url = $this->adminDailyListUrl('2025-12-26');
        $response = $this->actingAs($admin)->get($url);

        $response->assertStatus(200);
        $response->assertSee('2025');
        $response->assertSee('12');
        $response->assertSee('26');

        $response->assertSee($user->name);
        $response->assertSee('10:00');
        $response->assertSee('19:00');
    }
}
