<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExternalSMENotes extends Model
{
    use HasFactory;
    protected $table = 'external_sme_note';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = ['id', 'onBoardingId', 'interviewId', 'notepad', 'updated_by'];
}
