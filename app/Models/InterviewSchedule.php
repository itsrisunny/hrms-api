<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;



class InterviewSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'onBoardingId', 'name', 'position', 'phone', 'email', 'skills', 'highest_qualification', 
        'first_company', 'doj_first', 'current_company', 'doj_current', 'rounds', 'reference'
    ];

    public function certifications()
    {
        return $this->hasMany(Certification::class);
    }

    public function interviewRounds()
    {
        return $this->hasMany(InterviewRound::class);
    }
    
    public function interviewNotes()
    {
        return $this->hasMany(InterviewNote::class, 'interviewId', 'id');
    }
    
    public function onBoarding()
    {
        return $this->belongsTo(OnBoarding::class, 'onBoardingId');
    }
}