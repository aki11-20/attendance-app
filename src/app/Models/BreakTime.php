<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BreakTime extends Model
{
    use HasFactory;
    
    protected $table = 'breaks';

    protected $fillable = [
        'attendance_id',
        'break_no',
        'break_start',
        'break_end',
    ];

    protected $casts = [
        'break_no' => 'integer',
        'break_start' => 'datetime',
        'break_end' => 'datetime',
    ];

    public function attendance() {
        return $this->belongsTo(Attendance::class);
    }
}
