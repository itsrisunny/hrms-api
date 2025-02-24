<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OnBoarding;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use App\Models\InterviewSchedule;
use App\Models\Certification;
use App\Models\InterviewRound;
use App\Models\Smtp; // Add this line
use Illuminate\Support\Facades\Mail; // Add this line
use App\Models\Employee; // Add this line
use App\Models\JObPost; // Add this line
use App\Models\InterviewNote;
class OnBoardingController extends Controller
{
    //

    public function uploadAndSaveResume(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'mobile' => 'required|string|max:15',
            'apply_for' => 'required|string|max:255',
            'skills' => 'required|array',
            'resume' => 'required|file|mimes:pdf,doc,docx|max:2048',
        ]);

        // Store the physical file
        $file = $request->file('resume');
        $filePath = $file->store('public/resumes/');

        // Save the file and data to the database
        $onBoarding = new OnBoarding();
        $onBoarding->name = $request->input('name');
        $onBoarding->email = $request->input('email');
        $onBoarding->mobile = $request->input('mobile');
        $onBoarding->apply_for = $request->input('apply_for');
        $onBoarding->skills = implode(', ', $request->input('skills'));
        $onBoarding->resume_path = $filePath;
        $onBoarding->save();

        return response()->json(['message' => 'Resume data saved successfully']);
    }

    public function listOnBoardingRecords()
    {
        $records = OnBoarding::with(['jobPost', 'interviewSchedule.interviewRounds'])->get()->map(function ($record) {
            $record->applied_on = Carbon::parse($record->created_at)->format('D, d M Y');
            $record->resume_path = url(Storage::url($record->resume_path));
            $record->interview_rounds = $record->interviewSchedule->flatMap->interviewRounds;
            return $record;
        });
        return response()->json($records);
    }

    public function createStorageLink()
    {
        Artisan::call('storage:link');
        return response()->json(['message' => 'Storage link created successfully']);
    }

    public function scheduleInterview(Request $request)
    {
        $this->validate($request, [
            'onBoardingId' => 'required',
            'name' => 'required|string|max:255',
            'position' => 'required|string|max:255',
            'phone' => 'required|string|max:15',
            'email' => 'required|email|max:255',
            'skills' => 'required|string|max:255',
            'highestQualification' => 'required|string|max:255',
            'firstCompany' => 'required|string|max:255',
            'dojFirst' => 'required|date',
            'currentCompany' => 'required|string|max:255',
            'dojCurrent' => 'required|date',
            'rounds' => 'required|integer',
            'interviewRounds' => 'required|array',
            'interviewRounds.*.interviewName' => 'required|max:255',
            'interviewRounds.*.date' => 'required|date',
            'interviewRounds.*.meetingType' => 'required|string|max:255',
            'interviewRounds.*.meetingLink' => 'nullable|string|max:255',
        ]);

        $interviewSchedule = InterviewSchedule::updateOrCreate(
            ['onBoardingId' => $request->onBoardingId],
            [
                'onBoardingId' => $request->onBoardingId,
                'name' => $request->name,
                'position' => $request->position,
                'phone' => $request->phone,
                'email' => $request->email,
                'skills' => $request->skills,
                'highest_qualification' => $request->highestQualification,
                'first_company' => $request->firstCompany,
                'doj_first' => $request->dojFirst,
                'current_company' => $request->currentCompany,
                'doj_current' => $request->dojCurrent,
                'rounds' => $request->rounds,
                'reference' => $request->reference,
            ]
        );
        $onBoarding = OnBoarding::find($request->onBoardingId);
        if ($onBoarding) {
            $onBoarding->apply_for = $request->position;
            $onBoarding->save();
        }
        foreach ($request->certifications as $certification) {
            Certification::updateOrCreate(
                ['interview_schedule_id' => $interviewSchedule->id, 'certification' => $certification['certification']],
                ['year' => $certification['year']]
            );
        }
        $smtp = Smtp::first();
        config(['mail.mailers.smtp.host' => $smtp->host]);
        config(['mail.mailers.smtp.port' => $smtp->port]);
        config(['mail.mailers.smtp.username' => $smtp->username]);
        config(['mail.mailers.smtp.password' => $smtp->password]);
        config(['mail.mailers.smtp.encryption' => 'tls']);
        $interviewRoundsData = [];
        foreach ($request->interviewRounds as $round) {
            $existingRound = InterviewRound::where('interview_schedule_id', $interviewSchedule->id)
                                           ->where('round', $round['round'])
                                           ->first();

            $dateChanged = !$existingRound || $existingRound->date != $round['date'];
            InterviewRound::updateOrCreate(
                ['interview_schedule_id' => $interviewSchedule->id, 'round' => $round['round']],
                [
                    'interview_name' => $round['interviewName'],
                    'date' => Carbon::createFromFormat('m/d/Y, h:i A', $round['date'])->format('Y-m-d H:i:s'),
                    'meeting_type' => $round['meetingType'],
                    'meeting_link' => $round['meetingLink'],
                ]
            );

            if ($dateChanged) {
                $employee = Employee::find($round['interviewName']);
                if ($employee) {
                    $interviewScheduleData = [
                        'candidateName' => $interviewSchedule->name,
                        'position' => $interviewSchedule->position,
                        'email' => $interviewSchedule->email,
                        'mobile' => $interviewSchedule->phone,
                        'round' => $round['round'],
                        'date' => Carbon::parse($round['date'])->format('d-m-Y h:i A'),
                        'meetingType' => $round['meetingType'],
                        'meetingLink' => $round['meetingLink'],
                        'employeeName' => $employee->FirstName." ".$employee->LastName,
                        'employeeEmail' => $employee->email,
                    ];

                    $interviewRoundsData[] = $interviewScheduleData;

                    // Send email to interviewer
                   Mail::send('emails.interview_schedule', ['interviewSchedule' => (object) $interviewScheduleData], function ($message) use ($employee) {
                        $message->from('spherehrms@apisod.ai', 'HRMS Portal')
                                ->to($employee->email)
                                ->subject('Interview Schedule');
                    });
                }
            }
        }

        if (!empty($interviewRoundsData) && $dateChanged) {
            // Send email to candidate
            $candidateEmailData = [
                'candidateName' => $interviewSchedule->name,
                'position' => $interviewSchedule->position,
                'email' => $interviewSchedule->email,
                'mobile' => $interviewSchedule->phone,
                'interviewRounds' => $interviewRoundsData,
            ];
           Mail::send('emails.candidate_interview_schedule', ['interviewSchedule' => (object) $candidateEmailData], function ($message) use ($interviewSchedule) {
                $message->from('spherehrms@apisod.ai', 'HRMS Portal')
                        ->to($interviewSchedule->email)
                        ->subject('Your Interview Schedule');
            });
        }

        return response()->json(['message' => 'Interview schedule data saved successfully']);
    }

    public function interviewDetail(Request $request)
    {
        $interviewDetails = InterviewSchedule::with(['certifications', 'interviewRounds'])->get()->map(function ($interview) {
            $interview->created_at = Carbon::parse($interview->created_at)->format('d-m-Y');
            return $interview;
        });

        return response()->json($interviewDetails);
    }
    public function individualInterviewDetail(Request $request)
    {
        $this->validate($request, [
            'onBoardingId' => 'required',
        ]);

        $interviewDetails = InterviewSchedule::with(['certifications', 'interviewRounds'])->where('onBoardingId', $request->onBoardingId)->get()->map(function ($interview) {
            $interview->interviewRounds->each(function ($round) {
                $round->interviewNotes = $round->interviewNotes->first();
            });
            return $interview;
        });

        return response()->json($interviewDetails);
    }
    public function saveNotepad(Request $request)
    {
        $this->validate($request, [
            'onBoardingId' => 'required',
            'interviewId' => 'required',
            'notepad' => 'required|string',
            'updated_by' => 'required',
            'status' => 'required', // Add this line
        ]);

        $interviewNote = InterviewNote::updateOrCreate(
            ['onBoardingId' => $request->onBoardingId, 'interviewId' => $request->interviewId],
            ['notepad' => $request->notepad, 'updated_by' => $request->updated_by]
        );

        // Update the status in InterviewRound model
        InterviewRound::where('id', $request->interviewId)
                      ->update(['status' => $request->status]);

        return response()->json(['message' => 'Notepad data and status updated successfully']);
    }
    public function reSceduleInterview(Request $request)
    {
        $this->validate($request, [
            'interviewId' => 'required',
            'round' => 'required',
            'date' => 'required',
            'interviewName' => 'required', // Add this line
        ]);

        $interviewRound = InterviewRound::where('interview_schedule_id', $request->interviewId)
            ->where('round', $request->round)  // Add this line to check the round
            ->first();

        $dateChanged = $interviewRound && $interviewRound->date != Carbon::parse($request->date)->format('Y-m-d H:i:s');
        $nameChanged = $interviewRound && $interviewRound->interview_name != $request->interviewName;
        $meetingTypeChanged = $interviewRound && $interviewRound->meeting_type != $request->meetingType;

        $interviewRound->update([
            'date' => Carbon::parse($request->date)->format('Y-m-d H:i:s'),
            'meeting_type' => $request->meetingType,
            'meeting_link' => $request->meetingLink,
            'interview_name' => $request->interviewName,
        ]);

        $smtp = Smtp::first();
        config(['mail.mailers.smtp.host' => $smtp->host]);
        config(['mail.mailers.smtp.port' => $smtp->port]);
        config(['mail.mailers.smtp.username' => $smtp->username]);
        config(['mail.mailers.smtp.password' => $smtp->password]);
        config(['mail.mailers.smtp.encryption' => 'tls']);

        if ($dateChanged || $nameChanged || $meetingTypeChanged) {
            $employee = Employee::find($request->interviewName);
            $interviewSchedule = InterviewSchedule::find($request->interviewId);

            if ($employee && $interviewSchedule) {
                $interviewScheduleData = [
                    'candidateName' => $interviewSchedule->name,
                    'position' => $interviewSchedule->position,
                    'email' => $interviewSchedule->email,
                    'mobile' => $interviewSchedule->phone,
                    'round' => $request->round,
                    'date' => Carbon::parse($request->date)->format('d-m-Y h:i A'),
                    'meetingType' => $request->meetingType,
                    'meetingLink' => $request->meetingLink,
                    'employeeName' => $employee->FirstName . " " . $employee->LastName,
                    'employeeEmail' => $employee->email,
                ];

                // Send email to interviewer
                Mail::send('emails.interview_schedule', ['interviewSchedule' => (object) $interviewScheduleData], function ($message) use ($employee) {
                    $message->from('spherehrms@apisod.ai', 'HRMS Portal')
                        ->to($employee->email)
                        ->subject('Interview Rescheduled');
                });

                // Send email to candidate
                $candidateEmailData = [
                    'candidateName' => $interviewSchedule->name,
                    'position' => $interviewSchedule->position,
                    'email' => $interviewSchedule->email,
                    'mobile' => $interviewSchedule->phone,
                    'interviewRounds' => [$interviewScheduleData],
                ];
                Mail::send('emails.candidate_interview_schedule', ['interviewSchedule' => (object) $candidateEmailData], function ($message) use ($interviewSchedule) {
                    $message->from('spherehrms@apisod.ai', 'HRMS Portal')
                        ->to($interviewSchedule->email)
                        ->subject('Your Interview Rescheduled');
                });
            }
        }

        return response()->json(['message' => 'Interview rescheduled successfully']);
    }
    public function InterviewList(Request $request)
    {
        $this->validate($request, [
            'employee_id' => 'required',
        ]);

        $interviewRounds = InterviewRound::where('interview_name', $request->employee_id)
            ->with(['interviewSchedule.onBoarding.jobPost', 'interviewNotes'])
            ->get()
            ->map(function ($round) {
                return [
                    'id' => $round->id,
                    'interview_schedule_id' => $round->interview_schedule_id,
                    'onBoardingId' => $round->interviewSchedule->onBoardingId,
                    'interview_notes_id' => optional($round->interviewNotes->first())->id,
                    'round' => $round->round,
                    'interview_name' => $round->interview_name,
                    'date' => Carbon::parse($round->date)->format('d M Y h:i A'),
                    'meeting_type' => $round->meeting_type,
                    'meeting_link' => $round->meeting_link,
                    'status' => $round->status,
                    'name' => $round->interviewSchedule->onBoarding->name,
                    'job_title' => optional($round->interviewSchedule->onBoarding->jobPost)->job_title,
                    'resume_path' => url(Storage::url($round->interviewSchedule->onBoarding->resume_path)),
                ];
            });

        return response()->json($interviewRounds);
    }
    public function applyOnJob(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'mobile' => 'required|string|max:15',
            'skills' => 'required|array',
            'resume' => 'required|file|mimes:pdf,doc,docx|max:2048',
        ]);

        // Store the physical file
        $file = $request->file('resume');
        $filePath = $file->store('public/resumes/');

        // Save the file and data to the database
        $onBoarding = new OnBoarding();
        $onBoarding->name = $request->input('name');
        $onBoarding->email = $request->input('email');
        $onBoarding->mobile = $request->input('mobile');
        $onBoarding->apply_for = $request->input('apply_for')?$request->input('apply_for'):"-";
        $onBoarding->skills = implode(', ', $request->input('skills'));
        $onBoarding->resume_path = $filePath;
        $onBoarding->save();
        if($request->input('apply_for')){
            return response()->json(['message' => 'You have successfully applied for this Job.']);
        }else{
            return response()->json(['message' => 'Resume uploaded successfully.']);
        }
        
    }
}
