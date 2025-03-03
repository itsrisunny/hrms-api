<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InterviewRound extends Model
{
    use HasFactory;

    protected $fillable = [
        'interview_schedule_id', 'round', 'interview_name', 'date', 'probe_area', 'meeting_type', 'meeting_link', 'status'
    ];

    public function interviewSchedule()
    {
        return $this->belongsTo(InterviewSchedule::class);
    }
    public function interviewNotes()
    {
        return $this->hasMany(InterviewNote::class, 'interviewId', 'id');
    }
}