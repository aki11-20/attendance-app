<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Auth\Notifications\VerifyEmail;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     *
     * @return void
     */

    private function registerUrl(): string {
        return Route::has('register') ? route('register') : '/register';
    }

    private function verificationNoticeUrl(): string {
        return Route::has('verification.notice') ? route('verification.notice') : '/email/verify';
    }

    private function attendanceUrl(): string {
        return Route::has('attendance.index') ? route('attendance.index') : '/attendance';
    }

    private function seedUnverifiedUser(array $attrs = []): User {
        return User::factory()->create(array_merge([
            'email_verified_at' => null,
        ], $attrs));
    }

    public function test_verification_email_is_sent_after_registration(): void
    {
        Notification::fake();

        $payload = [
            'name' => 'メール認証テスト',
            'email' => 'verify_test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->post($this->registerUrl(), $payload);
        $response->assertStatus(302);

        $user = User::where('email', $payload['email'])->first();
        $this->assertNotNull($user);

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_verification_notice_page_has_verify_button_and_can_resend(): void {
        Notification::fake();

        $user = $this->seedUnverifiedUser([
            'email' => 'notice_test@example.com',
        ]);

        $response = $this->actingAs($user)->get($this->verificationNoticeUrl());
        $response->assertStatus(200);

        $response->assertSee('認証はこちらから');

        $sendUrl = null;
        if (Route::has('verification.send')) {
            $sendUrl = route('verification.send');
        } else {
            $sendUrl = '/email/verification-notification';
        }

        $response = $this->actingAs($user)->post($sendUrl);
        $response->assertStatus(302);

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_email_verification_completes_and_redirects_to_attendance_page(): void {
        $user = $this->seedUnverifiedUser([
            'email' => 'complete_test@example.com',
        ]);

        if (!Route::has('verification.verify')) {
            $this->fail('Route [verification.verify] が存在しません。');
        }

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        $response->assertStatus(302);
        $response->assertRedirect($this->attendanceUrl());

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }
}
