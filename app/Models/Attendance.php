<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $table = 'attendance';
    protected $primaryKey = 'EmployeeID';
    public $timestamps = false;
    protected $fillable = [
        'EmployeeID',
        'PunchInTime',
        'PunchOutTime'
        // Add other fields as needed
    ];
}
