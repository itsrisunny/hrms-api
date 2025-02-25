<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SME extends Model
{
    use HasFactory;

    protected $fillable = [
        'sme_name',
        'sme_id',
        'sme_phone',
        'sme_email',
        'sme_expertise_area',
        'sme_linkedin_profile',
        'sme_temporary_email',
        'sme_temporary_password',
        'enable_temporary_values',
        'status',
    ];
}
