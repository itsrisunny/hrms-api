<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Department;

class DepartmentController extends Controller
{
    public function getDepartment(Request $request)
    {
        $department = Department::select('DepartmentID', 'DepartmentName')->get()->toArray();
        return response()->json(['data'=> $department, 'status' => 200]);
    }
}
