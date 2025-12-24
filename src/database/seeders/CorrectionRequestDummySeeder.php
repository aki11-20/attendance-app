<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use App\Models\CorrectionRequest;
use Carbon\Carbon;

class CorrectionRequestDummySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $correctionRequestDataList = [
            [
                'email' => 'reina.n@coachtech.com',
                'work_date' => '2025-12-01',
                'status' => CorrectionRequest::STATUS_PENDING,
                'requested_clock_in' => '2025-12-01 09:10:00',
                'requested_clock_out' => '2025-12-01 18:00:00',
                'requested_breaks' => [
                    ['start' => '2025-12-01 12:10:00', 'end' => '2025-12-01 13:10:00'],
                ],
                'comment' => '電車遅延のため',
            ],
            [
                'email' => 'taro.y@coachtech.com',
                'work_date' => '2025-12-01',
                'status' => CorrectionRequest::STATUS_PENDING,
                'requested_clock_in' => '2025-12-01 09:20:00',
                'requested_clock_out' => '2025-12-01 18:00:00',
                'requested_breaks' => [
                    ['start' => '2025-12-01 12:00:00', 'end' => '2025-12-01 13:00:00'],
                ],
                'comment' => '電車遅延のため',
            ],
            [
                'email' => 'hanako.y@coachtech.com',
                'work_date' => '2025-12-02',
                'status' => CorrectionRequest::STATUS_APPROVED,
                'requested_clock_in' => '2025-12-02 09:15:00',
                'requested_clock_out' => '2025-12-02 18:00:00',
                'requested_breaks' => [
                    ['start' => '2025-12-02 12:30:00', 'end' => '2025-12-02 13:30:00'],
                ],
                'comment' => '電車遅延のため',
            ],
        ];
        foreach ($correctionRequestDataList as $correctionRequestData) {
            $user = User::where('email', $correctionRequestData['email'])->first();
            if (!$user) {
                continue;
            }

            $attendance = Attendance::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'work_date' => $correctionRequestData['work_date'],
                ],
                [
                    'clock_in' => Carbon::parse($correctionRequestData['work_date'] . ' 09:00:00'),
                    'clock_out' => Carbon::parse($correctionRequestData['work_date'] . '18:00:00'),
                ]
            );

            CorrectionRequest::updateOrCreate(
                [
                    'attendance_id' => $attendance->id,
                    'user_id' => $user->id,
                    'status' => $correctionRequestData['status'],
                ],
                [
                    'requested_clock_in' => Carbon::parse($correctionRequestData['requested_clock_in']),
                    'requested_clock_out' => Carbon::parse($correctionRequestData['requested_clock_out']),
                    'requested_breaks' => $correctionRequestData['requested_breaks'],
                    'comment' => $correctionRequestData['comment'],
                ]
            );
        }
    }
}
