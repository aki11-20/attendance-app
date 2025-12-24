<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Attendance extends Model
{
    use HasFactory;
    
    public const STATUS_OFF = 0;
    public const STATUS_WORKING = 1;
    public const STATUS_BREAKING = 2;
    public const STATUS_DONE = 3;

    protected $fillable = [
        'user_id',
        'work_date',
        'clock_in',
        'clock_out',
        'status',
        'total_work_minutes',
        'total_break_minutes',
        'note',
    ];

    protected $casts = [
        'work_date' => 'date',
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
        'status' => 'integer',
        'total_work_minutes' => 'integer',
        'total_break_minutes' => 'integer',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function breaks() {
        return $this->hasMany(BreakTime::class, 'attendance_id');
    }

    public function correctionRequests() {
        return $this->hasMany(CorrectionRequest::class);
    }

    public function getStatusAttribute() {
        if (is_null($this->clock_in)) {
            return '勤務外';
        }
        if (!is_null($this->clock_out)) {
            return '退勤済';
        }
        $latestBreak = $this->breaks()->latest()->first();
        if ($latestBreak && is_null($latestBreak->break_end)) {
            return '休憩中';
        }
        return '出勤中';
    }
}
