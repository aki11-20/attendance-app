<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CorrectionRequest extends Model
{
    public const STATUS_PENDING = 0;
    public const STATUS_APPROVED = 1;
    public const STATUS_REJECTED = 2;

    protected $fillable = [
        'attendance_id',
        'user_id',
        'status',
        'requested_clock_in',
        'requested_clock_out',
        'requested_breaks',
        'comment',
    ];

    protected $casts = [
        'status' => 'integer',
        'requested_clock_in' => 'datetime',
        'requested_clock_out' => 'datetime',
        'requested_breaks' => 'array',
    ];

    public function attendance() {
        return $this->belongsTo(Attendance::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }
}
