<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Leave extends Model
{
    protected $table = 'leaverequests';
    protected $primaryKey = 'LeaveRequestID';
    public $timestamps = false;
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    protected $fillable = [
        'LeaveRequestID',
        'EmployeeID',
        'LeaveType',
        'StartDate',
        'EndDate',
        'Status',
        'RequestDate',
        'Document',
        'Description',
        'UpdatedBy',
        'Reason'
        // Add other fields as needed
    ];
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'EmployeeID');
    }
}
