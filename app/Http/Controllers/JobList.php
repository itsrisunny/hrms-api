<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\JobFunction;
use App\Models\JObPost; // Corrected the class name
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
class JobList extends Controller
{
    public function listJobsByJobFunctionGroup(Request $request)
    {
        $jobFunctions = JobFunction::withCount(['jobPosts' => function ($query) {
                $query->where('status', 1);
            }])
            ->get()
            ->map(function ($jobFunction) {
                return [
                    'name' => $jobFunction->name,
                    'icon' => $jobFunction->icon?url(Storage::url($jobFunction->icon)):"",
                    'total' => $jobFunction->job_posts_count
                ];
            })
            ->filter(function ($jobFunction) {
                return $jobFunction['total'] > 0;
            })
            ->values(); // Ensure the collection is re-indexed

        $totalActiveJobs = $jobFunctions->sum('total');

        $currentMonthJobs = JObPost::where('job_status', "Active")
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();

        return response()->json([
            'jobFunctions' => $jobFunctions,
            'totalActiveJobs' => $totalActiveJobs,
            'currentMonthJobs' => $currentMonthJobs
        ]);
    }
    public function listActiveJobs(Request $request)
    {
        $limit = $request->input('limit', 10);
        $page = $request->input('page', 1);
        $jobTitle = $request->input('job_title', '');
        $location = $request->input('location', '');
        $jobFunctionId = $request->input('job_function_id', '');
        $offset = ($page - 1) * $limit;

        $query = JObPost::where('job_status', "Active")
            ->with('jobFunction'); // Join with jobFunction

        if (!empty($jobTitle)) {
            $query->where(function ($q) use ($jobTitle) {
                $q->where('job_title', 'like', '%' . $jobTitle . '%')
                  ->orWhere('skills_required', 'like', '%' . $jobTitle . '%')
                  ->orWhere('job_id', 'like', '%' . $jobTitle . '%');
            });
        }

        if (!empty($location)) {
            $query->where('location', 'like', '%' . $location . '%');
        }

        if (!empty($jobFunctionId)) {
            $query->where('job_function', $jobFunctionId);
        }

        $jobPosts = $query->skip($offset)
            ->take($limit)
            ->get()
            ->map(function ($jobPost) {
                $jobPost->job_posting_date = Carbon::parse($jobPost->job_posting_date)->format('d M Y');
                $jobPost->application_deadline = Carbon::parse($jobPost->application_deadline)->format('d M Y');
                $jobPost->skills_required = json_decode($jobPost->skills_required);
                $jobPost->contact_information = json_decode($jobPost->contact_information);
                $jobPost->benefits = json_decode($jobPost->benefits);
                return $jobPost;
            });

        if ($jobPosts->isEmpty()) {
            return response()->json(['message' => 'No job post found', 'data' => [], 'status' => 404]);
        }

        return response()->json(['message' => 'Records found!', 'data' => $jobPosts, 'status' => 200]);
    }
}
