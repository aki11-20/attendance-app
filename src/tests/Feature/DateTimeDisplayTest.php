<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Carbon;
use App\Models\User;

class DateTimeDisplayTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_current_datetime_matches_ui_format()
    {
        Carbon::setTestNow(Carbon::create(2025, 12, 25, 14, 17, 0, 'Asia/Tokyo'));

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('2025年12月25日(木)');
        $response->assertSee('14:17');
    }
}
