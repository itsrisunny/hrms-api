<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveBalance extends Model
{
    protected $table = 'leavebalances';
    protected $primaryKey = 'LeaveBalanceID';
    public $timestamps = false;
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    protected $fillable = [
        'LeaveBalanceID',
        'EmployeeID',
        'RemainingLeave',
        'TotalLeave',
        'UsedLeave'
    ];
}
