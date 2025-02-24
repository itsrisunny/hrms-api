<?php

namespace App\Http\Controllers;

use App\Models\JObPost;
use App\Models\JobFunction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Setting;
class JObPostController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\JObPost  $jObPost
     * @return \Illuminate\Http\Response
     */
    public function show(JObPost $jObPost)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\JObPost  $jObPost
     * @return \Illuminate\Http\Response
     */
    public function edit(JObPost $jObPost)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\JObPost  $jObPost
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, JObPost $jObPost)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\JObPost  $jObPost
     * @return \Illuminate\Http\Response
     */
    public function destroy(JObPost $jObPost)
    {
        //
    }

    public function saveJobPost(Request $request, $id = null)
    {
        $validatedData = $request->validate([
            'company_name' => 'required|string|max:255',
            'job_id' => 'required|string|max:255',
            'job_title' => 'required|string|max:255',
            'skills_required' => 'required|array',
            'skills_required.*' => 'string',
            'experience_required' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'job_type' => 'required|string|max:255',
            'job_function' => 'required|string|max:255',
            'job_posting_date' => 'required|date',
            'application_deadline' => 'required|date',
            'contact_information' => 'required|array',
            'contact_information.email' => 'required|string|email|max:255',
            'required_education' => 'required|string|max:255',
            'job_status' => 'required|string|max:255',
            'benefits' => 'required|array',
            'benefits.*' => 'string',
            'salary_range' => 'required|string|max:255',
            'work_model' => 'required|string|max:255',
            'shift_timing' => 'required|string|max:255',
            'travel_required' => 'required|string|max:255',
            'job_description' => 'required|string',
            'how_to_apply' => 'required|string',
        ]);

        // Convert arrays to JSON strings
        $validatedData['skills_required'] = json_encode($validatedData['skills_required']);
        $validatedData['contact_information'] = json_encode($validatedData['contact_information']);
        $validatedData['benefits'] = json_encode($validatedData['benefits']);

        if ($id) {
            $jobPost = JObPost::findOrFail($id);
            $jobPost->update($validatedData);
            $message = 'Job post updated successfully';
        } else {
            $jobPost = new JObPost($validatedData);
            $jobPost->save();
            $message = 'Job post saved successfully';
        }

        return response()->json(['message' => $message], 201);
    }
    public function autogenerateJobId(Request $request)
    {
        $companySetting = Setting::where('type', 'CompanyName')->first();

        if (!$companySetting) {
            return response()->json(['message' => 'Company name not found in settings', 'status' => 404]);
        }

        $companyName = strtoupper(substr($companySetting->value, 0, 3));
        $date = \Carbon\Carbon::now()->format('dmY');
        $counter = DB::table('j_ob_posts')->whereDate('created_at', \Carbon\Carbon::today())->count() + 1;
        $jobId = $companyName . $date . str_pad($counter, 3, '0', STR_PAD_LEFT);

        return response()->json(['job_id' => $jobId], 200);
    }
    public function listJobs()
    {
        $jobPosts = JObPost::all()->map(function ($jobPost) {
            $jobPost->job_posting_date = \Carbon\Carbon::parse($jobPost->job_posting_date)->format('d M Y');
            $jobPost->application_deadline = \Carbon\Carbon::parse($jobPost->application_deadline)->format('d M Y');
            $jobPost->skills_required = json_decode($jobPost->skills_required);
            $jobPost->contact_information = json_decode($jobPost->contact_information);
            $jobPost->benefits = json_decode($jobPost->benefits);
            return $jobPost;
        });

        if ($jobPosts->isEmpty()) {
            return response()->json(['message' => 'No job post found', 'data' => [], 'status'=>404]);
        }

        return response()->json(['message' => 'records found!', 'data' => $jobPosts, 'status'=>200]);
    }
    public function listDetail(Request $request)
    {
        $validatedData = $request->validate([
            'id' => 'required',
        ]);

        $jobPost = JObPost::where('id', $validatedData['id'])->first();

        if (!$jobPost) {
            return response()->json(['message' => 'Job post not found', 'status' => 404]);
        }

        $jobPost->job_posting_date = \Carbon\Carbon::parse($jobPost->job_posting_date)->format('d M Y');
        $jobPost->application_deadline = \Carbon\Carbon::parse($jobPost->application_deadline)->format('d M Y');
        $jobPost->skills_required = json_decode($jobPost->skills_required);
        $jobPost->contact_information = json_decode($jobPost->contact_information);
        $jobPost->benefits = json_decode($jobPost->benefits);

        return response()->json(['message' => 'Job post found!', 'data' => $jobPost, 'status' => 200]);
    }
    public function jobFunctions()
    {
        $jobFunctions = JobFunction::select('id','name')->get();

        if ($jobFunctions->isEmpty()) {
            return response()->json(['message' => 'No job functions found', 'data' => [], 'status' => 404]);
        }

        return response()->json(['message' => 'Job functions found!', 'data' => $jobFunctions, 'status' => 200]);
    }
}
