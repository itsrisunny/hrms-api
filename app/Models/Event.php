<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $table = 'events';
    protected $primaryKey = 'EventID';
    public $timestamps = false;
    protected $fillable = [
        'EventID',
        'EmployeeID',
        'EventType',
        'EventName',
        'EventStartDate',
        'EventEndDate',
        'Description'
        // Add other fields as needed
    ];
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'EmployeeID');
    }
}
