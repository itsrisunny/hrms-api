<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExternalSme extends Model
{
    use HasFactory;

    protected $fillable = [
        'interview_schedule_id', 'round', 'interview_name', 'date', 'meeting_type', 'meeting_link', 'probe_area', 'status'
    ];

    protected $table = 'external_smes';
    public $timestamps = false; // Disable timestamps

    public function interviewSchedule()
    {
        return $this->belongsTo(InterviewSchedule::class, 'interview_schedule_id');
    }
}
