<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Certification extends Model
{
    use HasFactory;

    protected $fillable = [
        'interview_schedule_id', 'certification', 'year'
    ];

    public function interviewSchedule()
    {
        return $this->belongsTo(InterviewSchedule::class);
    }
}