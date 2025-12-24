<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;

class AttendanceDummySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = User::where('role', 0)->get();

        $start = Carbon::create(2025, 12, 1);
        $end = Carbon::create(2025, 12, 31);

        foreach ($users as $user) {
            $date = $start->copy();

            while ($date->lte($end)) {
                if ($date->isWeekend()) {
                    $date->addDay();
                    continue;
                }

                $clockIn = Carbon::parse($date->toDateString().' 09:00:00');
                $clockOut = Carbon::parse($date->toDateString().'18:00:00');

                $attendance = Attendance::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'work_date' => $date->toDateString(),
                    ],
                    [
                        'clock_in' => $clockIn,
                        'clock_out' => $clockOut,
                        'status' => defined(Attendance::class.'::STATUS_DONE') ? Attendance::STATUS_DONE : 0,
                    ]
                );

                $attendance->breaks()->delete();

                BreakTime::create([
                    'attendance_id' => $attendance->id,
                    'break_no' => 1,
                    'break_start' => Carbon::parse($date->toDateString().' 12:00:00'),
                    'break_end' => Carbon::parse($date->toDateString().' 13:00:00'),
                ]);

                $totalWorkMinutes = $clockIn->diffInMinutes($clockOut);
                $totalBreakMinutes = 60;
                $net = max($totalWorkMinutes - $totalBreakMinutes, 0);
                
                $attendance->total_break_minutes = $totalBreakMinutes;
                $attendance->total_work_minutes = $net;
                $attendance->save();

                $date->addDay();
            }
        }
    }
}
