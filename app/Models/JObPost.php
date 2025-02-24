<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JObPost extends Model
{
    use HasFactory;
    protected $table = 'j_ob_posts';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = [
        'company_name',
        'job_id',
        'job_title',
        'skills_required',
        'experience_required',
        'location',
        'job_type',
        'job_function',
        'job_posting_date',
        'application_deadline',
        'contact_information',
        'required_education',
        'job_status',
        'benefits',
        'salary_range',
        'work_model',
        'shift_timing',
        'travel_required',
        'job_description',
        'how_to_apply',
    ];
    public function jobFunction()
    {
        return $this->belongsTo(JobFunction::class, 'job_function'); // Define the relationship
    }
}
