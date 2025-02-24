<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\JObPost; // Add this line

class OnBoarding extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'email', 'mobile', 'apply_for', 'skills', 'resume_path'
    ];

    public function jobPost()
    {
        return $this->belongsTo(JObPost::class, 'apply_for', 'job_id');
    }
    public function interviewSchedule()
    {
        return $this->hasMany(InterviewSchedule::class, 'onBoardingId');
    }
}
