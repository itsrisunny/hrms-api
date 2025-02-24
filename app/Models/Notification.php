<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'notification';
    protected $primaryKey = 'NotificationID';
    public $timestamps = false;
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    protected $fillable = [
        'NotificationID',
        'EmployeeID',
        'Title',
        'Message',
        'IsRead'
    ];
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'EmployeeID');
    }
}
