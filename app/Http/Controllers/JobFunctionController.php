<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\JobFunction;
use Illuminate\Support\Facades\Storage;
class JobFunctionController extends Controller
{
    public function list(Request $request)
    {
        $jobFunctions = JobFunction::where('status', '!=', 90)
            ->orderBy('id', 'desc')
            ->get(['id', 'name', 'status', 'icon']); // Select only the required fields where status is not 90
        if ($jobFunctions->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'message' => 'No job functions found',
                'data' => []
            ]);
        }

        $jobFunctions->transform(function ($jobFunction) {
            $jobFunction->icon = $jobFunction->icon?url(Storage::url($jobFunction->icon)):"";
            return $jobFunction;
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Job functions retrieved successfully',
            'data' => $jobFunctions
        ]);
    }
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:0,1,90'
        ]);

        $jobFunction = JobFunction::find($id);
        if (!$jobFunction) {
            return response()->json([
                'status' => 'error',
                'message' => 'Job function not found'
            ], 404);
        }

        $action = $request->input('status');
        if ($action == 1) {
            $jobFunction->status = 1;
            $message = 'Job function activated successfully';
        } elseif ($action == 0) {
            $jobFunction->status = 0;
            $message = 'Job function deactivated successfully';
        } elseif ($action == 90) {
            $jobFunction->status = 90;
            $jobFunction->save();
            return response()->json([
                'status' => 'success',
                'message' => 'Job function marked as deleted successfully'
            ]);
        }

        $jobFunction->save();
        return response()->json([
            'status' => 'success',
            'message' => $message
        ]);
    }
    public function edit(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'status' => 'required|in:0,1,90'
        ]);

        $jobFunction = JobFunction::find($id);
        if (!$jobFunction) {
            return response()->json([
                'status' => 'error',
                'message' => 'Job function not found'
            ], 404);
        }

        $jobFunction->name = $request->input('name');
        if ($request->hasFile('icon')) {
            $iconPath = $request->file('icon')->store('icons', 'public');
            $jobFunction->icon = $iconPath;
        }
        $jobFunction->status = $request->input('status');
        $jobFunction->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Job function updated successfully',
            'data' => $jobFunction
        ]);
    }

    public function add(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'required|file|mimes:jpeg,png,jpg,gif,svg|max:2048'
        ]);

        $jobFunction = new JobFunction();
        $jobFunction->name = $request->input('name');
        $iconPath = $request->file('icon')->store('icons', 'public');
        $jobFunction->icon = $iconPath;
        $jobFunction->status = 1;
        $jobFunction->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Job function added successfully',
            'data' => $jobFunction
        ]);
    }
}
