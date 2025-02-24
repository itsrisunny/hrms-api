<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobFunction extends Model
{
    use HasFactory;
    protected $table = 'job_functions';
    protected $primaryKey = 'id';
    public $timestamps = false;
    public function jobPosts()
    {
        return $this->hasMany(JObPost::class, 'job_function');
    }
}
