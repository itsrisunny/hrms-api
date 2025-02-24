<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceRequest extends Model
{
    protected $table = 'attendancerequests';
    protected $primaryKey = 'RequestID';
    public $timestamps = false;
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    protected $fillable = [
        'RequestID',
        'EmployeeID',
        'RequestDate',
        'RequestType',
        'Status',
        'Document',
        'Reason'
        // Add other fields as needed
    ];
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'EmployeeID');
    }
}
