<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Employee extends Authenticatable implements JWTSubject
{
    protected $table = 'employees';
    protected $primaryKey = 'EmployeeID';
    public $timestamps = false;
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    protected $fillable = [
        'FirstName', 'LastName', 'DateOfBirth', 'Gender', 'HireDate', 'RoleID', 'DepartmentID',
        'ManagerID', 'CurrentAddress', 'PermanentAddress', 'Phone', 'password', 'email',
        'EmergencyContactNumber', 'BloodGroup', 'Status', 'DateOfJoining', 'Nationality',
        'MaritalStatus', 'ProfilePicture', 'createdBy'
    ];
    
    protected $hidden = [
        'Password',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }
    public function getJWTCustomClaims()
    {
        return [];
    }
    public function manager()
    {
        return $this->belongsTo(Employee::class, 'ManagerID')->select(['EmployeeID', 'FirstName', 'LastName']);
    }
    public function role()
    {
        return $this->belongsTo(Roles::class, 'RoleID');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'DepartmentID');
    }
}

