<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InterviewNote extends Model
{
    use HasFactory;
    protected $table = 'interview_note';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = ['id','onBoardingId', 'interviewId', 'notepad', 'updated_by'];
}