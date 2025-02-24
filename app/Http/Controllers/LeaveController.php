<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Leave;
use App\Models\Event;
use App\Models\LeaveBalance;
use App\Models\EmployeeLeaveBalance;
use App\Models\Smtp;
use App\Models\AttendanceRequest;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
class LeaveController extends Controller
{
    public function leaveRequest(Request $request)
    {
        $request->validate([
            'leaveType' => 'required',
            'fromDate' => 'required',
            'toDate' => 'required',
            'halfDay' => 'required',
            'reason' => 'required',
            'files' => 'array',
            'files.*' => 'mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx|max:2048'
        ]);
        $leave = new Leave();
        $leave->EmployeeID = $request->input('EmployeeID');
        $leave->LeaveType = $request->input('leaveType');
        $leave->HalfFull = $request->input('halfDay');
        $leave->StartDate = $request->input('fromDate');
        $leave->EndDate = $request->input('toDate');
        $leave->Status = 'Pending';
        $leave->RequestDate = Carbon::now();
        $leave->Description = $request->input('reason');
        $leave->createdAt = Carbon::now();
        $fileData = [];
        if ($request->has('files')) {
            $files = $request->file('files');
            foreach ($files as $file) {
                $filename = Str::uuid().time() . '.' . $file->getClientOriginalExtension();
                $file->storeAs('public/leave/'.$leave->EmployeeID.'/', $filename);
                $fileData[] = [ 
                    'filename' => $filename
                ];                
            }
            $leave->Document = json_encode($fileData);
        }
        $leave->save();
        return response()->json(['message' => 'Leave has been applied successfully.'], 201);
    }
    public function leaveRequestList(Request $request)
    {
        if($request->EmployeeID){
            $leaves = Leave::with(['employee'])
                ->where('EmployeeID', $request->EmployeeID)
                ->where('Status', '!=', 'Deleted')
                ->where('Status', '!=', 'Deducted')
                ->orderBy('LeaveRequestID', 'desc')
                ->get();
        }else{
            $leaves = Leave::with(['employee'])
                ->where('Status', '!=', 'Deleted')
                ->where('Status', '!=', 'Deducted')
                ->orderBy('LeaveRequestID', 'desc')
                ->get();
        }
        
        foreach ($leaves as $leave) {
            $leave->StartDate = Carbon::parse($leave->StartDate)->format('d M Y');
            $leave->EndDate = Carbon::parse($leave->EndDate)->format('d M Y');
            $leave->RequestDate = Carbon::parse($leave->RequestDate)->format('d M Y');
            $leave->LeaveType = $leave->LeaveType?$leave->LeaveType." Leave":"-";
            $leave->employeeName = $leave->employee->FirstName . ' ' . $leave->employee->LastName;
            $leave->EmployeeProfilePhoto = $leave->employee->ProfilePicture?url(Storage::url('profile_pictures/' . $leave->employee->ProfilePicture)):url(Storage::url('profile_pictures/no-img.jpg'));
            if ($leave->Document) {
                if (str_starts_with($leave->Document, '[') && str_ends_with($leave->Document, ']')) {
                    $files = json_decode($leave->Document, true);
                    $documents = [];
                    foreach ($files as $file) {
                        $documents[] = url(Storage::url('leave/' .$leave->employee->EmployeeID.'/'. $file['filename']));
                    }
                    $leave->Document = $documents;
                } else {
                     
                    $leave->Document = array(url(Storage::url('leave/' .$leave->employee->EmployeeID.'/'. $leave->Document)));
                }
            } else {
                $leave->Document = null;
            }
            unset($leave->employee);
        }
        return response()->json(['data'=> $leaves, 'status' => 200]);
    }
    public function leaveManageStatus(Request $request)
    {
        $request->validate([
            'LeaveID' => 'required',
            'UserID' => 'required',
            'Status' => 'required',
        ]);
        $LeaveRequestID = $request->input('LeaveID');
        $UpdatedBy = $request->input('UserID');
        $Status = $request->input('Status');
        $Reason = $request->input('Reason');
        $leaveRequest = Leave::with(['employee'])->find($LeaveRequestID);
        if ($leaveRequest) {
            $leaveRequest->update([
                'Status' => $Status,
                'UpdatedBy' => $UpdatedBy,
                'Reason' => $Reason
            ]);
            switch ($Status) {
                case 'Approved':
                    $leaveDays = $this->calculateLeaveDays($leaveRequest->StartDate, $leaveRequest->EndDate);
                    $leaveBalance = LeaveBalance::where('EmployeeID', $leaveRequest->EmployeeID)->first();
                    $usedLeave = ($leaveRequest->HalfFull == "Full Day") ? $leaveDays : 0.5;
                    if ($leaveBalance) {
                        if ($leaveBalance->RemainingLeave > 0) {
                            $leaveBalance->RemainingLeave = max(0, $leaveBalance->RemainingLeave - $usedLeave);
                        }else{
                            $leaveBalance->RemainingLeave = 0;
                        }
                        $leaveBalance->UsedLeave += $usedLeave;
                        $leaveBalance->save();
                    }else {
                        LeaveBalance::create([
                            'EmployeeID' => $leaveRequest->EmployeeID,
                            'RemainingLeave' => 0,
                            'TotalLeave' => 0, 
                            'UsedLeave' => ($leaveRequest->HalfFull == "Full Day") ? $leaveDays : 0.5,
                        ]);
                    }
                    $message = 'Leave request approved successfully';
                    break;
                case 'Rejected':
                    $message = 'Leave request rejected successfully';
                    break;
                case 'Deleted':
                    $message = 'Leave request deleted successfully';
                    break;
                default:
                    $message = 'Leave request status updated successfully';
            }

            // Send email notification
            $this->sendLeaveStatusEmail(
                $leaveRequest->employee->email,
                $leaveRequest->employee->FirstName,
                $leaveRequest->RequestDate,
                $Status,
                $leaveRequest->StartDate,
                $leaveRequest->EndDate,
                $leaveRequest->HalfFull
            );

            return response()->json(['message' => $message, 'status' => 200]);
        } else {
            return response()->json(['error' => 'Invalid LeaveID'], 404);
        }
    }
    private function sendLeaveStatusEmail($email, $name, $dateRequested, $status, $startDate, $endDate, $halfFull)
    {
        $smtp = Smtp::first();
        config(['mail.mailers.smtp.host' => $smtp->host]);
        config(['mail.mailers.smtp.port' => $smtp->port]);
        config(['mail.mailers.smtp.username' => $smtp->username]);
        config(['mail.mailers.smtp.password' => $smtp->password]);
        config(['mail.mailers.smtp.encryption' => 'tls']);
        $subject = "Update on Your Leave Request";
        $dateRange = Carbon::parse($startDate)->format('d M Y');
        if ($startDate != $endDate) {
            $dateRange .= " - " . Carbon::parse($endDate)->format('d M Y');
        }
        $totalDays = $this->calculateLeaveDays($startDate, $endDate);
        $body = "
            <html>
            <body>
                <p>Hi " . $name . ",</p>
                <p>Your leave request for " . $dateRange . " (" . $totalDays . " " . $halfFull . "/s) has been reviewed and is now " . $status . ".</p>
                <p>If you have any questions or need further clarification, please contact your manager.</p>
                <p>Best regards,<br>Sphere HRMS</p>
            </body>
            </html>
        ";

        Mail::send([], [], function ($message) use ($email, $subject, $body) {
            $message->from('spherehrms@apisod.ai', 'HRMS Portal')
                    ->to($email)
                    ->subject($subject)
                    ->setBody($body, 'text/html');
        });
    }
    public function attendanceRequest(Request $request)
    {
        $request->validate([
            'EmployeeID' => 'required',
            'RequestDate' => 'required',
            'Reason' => 'required',
            'files' => 'array',
            'files.*' => 'mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx|max:2048'
        ]);
        $AttendanceRequest = new AttendanceRequest();
        $AttendanceRequest->EmployeeID = $request->input('EmployeeID');
        $AttendanceRequest->RequestDate = $request->input('RequestDate');
        $AttendanceRequest->Reason = $request->input('Reason');
        $AttendanceRequest->Status = 'Pending';
        $AttendanceRequest->RequestType = 'Punch In';
        $fileData = [];
        if ($request->has('files')) {
            $files = $request->file('files');
            foreach ($files as $file) {
                $filename = Str::uuid().time() . '.' . $file->getClientOriginalExtension();
                $file->storeAs('public/attendance/'.$AttendanceRequest->EmployeeID.'/', $filename);
                $fileData[] = [ 
                    'filename' => $filename
                ];                
            }
            $AttendanceRequest->Document = json_encode($fileData);
        }
        $AttendanceRequest->save();
        return response()->json(['message' => 'Attendence request has been applied successfully.'], 201);
    }
    public function attendanceRequestList(Request $request)
    {
        if($request->EmployeeID){
            $attendanceRequests = AttendanceRequest::with(['employee'])->where('EmployeeID', $request->EmployeeID) ->where('Status', '!=', 'Deleted')->orderBy('RequestID', 'desc')->get();
        }else{
            $attendanceRequests = AttendanceRequest::with(['employee']) ->where('Status', '!=', 'Deleted')->orderBy('RequestID', 'desc')->get();
        }
        foreach ($attendanceRequests as $attendanceRequest) {
            $attendanceRequest->RequestDate = Carbon::parse($attendanceRequest->RequestDate)->format('d M Y');
            $attendanceRequest->RequestType = $attendanceRequest->RequestType?$attendanceRequest->RequestType:"-";
            $attendanceRequest->employeeName = $attendanceRequest->employee->FirstName . ' ' . $attendanceRequest->employee->LastName;
            $attendanceRequest->EmployeeProfilePhoto = $attendanceRequest->employee->ProfilePicture?url(Storage::url('profile_pictures/' . $attendanceRequest->employee->ProfilePicture)):url(Storage::url('profile_pictures/no-img.jpg'));
            if ($attendanceRequest->Document) {
                if (str_starts_with($attendanceRequest->Document, '[') && str_ends_with($attendanceRequest->Document, ']')) {
                    $files = json_decode($attendanceRequest->Document, true);
                    $documents = [];
                    foreach ($files as $file) {
                        $documents[] = url(Storage::url('attendance/' .$attendanceRequest->employee->EmployeeID.'/'. $file['filename']));
                    }
                    $attendanceRequest->Document = $documents;
                } else {
                     
                    $attendanceRequest->Document = array(url(Storage::url('attendance/' .$attendanceRequest->employee->EmployeeID.'/'. $attendanceRequest->Document)));
                }
            } else {
                $attendanceRequest->Document = null;
            }
            unset($attendanceRequest->employee);
        }
        return response()->json(['data'=> $attendanceRequests, 'status' => 200]);
    }
    public function attendanceStatus(Request $request)
    {
        $request->validate([
            'AttendenceID' => 'required',
            'UserID' => 'required',
            'Status' => 'required',
        ]);
        $AttendanceRequestID = $request->input('AttendenceID');
        $UpdatedBy = $request->input('UserID');
        $Status = $request->input('Status');
        $attendanceRequest = AttendanceRequest::with(['employee'])->find($AttendanceRequestID);
        if ($attendanceRequest) {
            $attendanceRequest->update([
                'Status' => $Status,
                'UpdatedBy' => $UpdatedBy
            ]);
            switch ($Status) {
                case 'Approved':
                    $message =  'Already punch in on requested date '.Carbon::parse($attendanceRequest->RequestDate)->format('d M Y').".";
                    $attendanceCheck = Attendance::where('EmployeeID', $attendanceRequest->EmployeeID)->whereDate("PunchInTime", $attendanceRequest->RequestDate)->whereNull('PunchOutTime')->first();
                    if (!$attendanceCheck) {
                        $attendance = new Attendance();
                        $attendance->EmployeeID = $attendanceRequest->EmployeeID;
                        $attendance->PunchInTime = $attendanceRequest->RequestDate." 10:00:00";
                        $attendance->save();
                        $this->createEventAfterAttendanceCreate($attendanceRequest); 
                        $message =  'Attendence request approved successfully.';   
                        $leaveBalance = LeaveBalance::where('EmployeeID', $attendanceRequest->EmployeeID)->first();
                        if ($leaveBalance) {
                            $leaveBalance->RemainingLeave += 1;
                            $leaveBalance->UsedLeave -= 1;
                            $leaveBalance->save();
                        }                    
                        // Delete leave record with status 'Deducted'
                        Leave::where('EmployeeID', $attendanceRequest->EmployeeID)
                            ->where('Status', 'Deducted')
                            ->whereDate('StartDate', $attendanceRequest->RequestDate)
                            ->delete();
                    }
                    
                    break;
                case 'Rejected':
                    $message = 'Attendence request rejected successfully';
                    break;
                case 'Deleted':
                    $message = 'Attendence request deleted successfully';
                    break;
                default:
                    $message = 'Attendence request status updated successfully';
            }

            // Send email notification
            $this->sendAttendanceStatusEmail($attendanceRequest->employee->email, $attendanceRequest->employee->FirstName, $attendanceRequest->RequestDate, $Status);

            return response()->json(['message' => $message, 'status' => 200]);
        }else{
            return response()->json(['error' => 'Invalid attendence request id.'], 404);
        }
    }
    private function sendAttendanceStatusEmail($email, $name, $dateRequested, $status)
    {
        $smtp = Smtp::first();
        config(['mail.mailers.smtp.host' => $smtp->host]);
        config(['mail.mailers.smtp.port' => $smtp->port]);
        config(['mail.mailers.smtp.username' => $smtp->username]);
        config(['mail.mailers.smtp.password' => $smtp->password]);
        config(['mail.mailers.smtp.encryption' => 'tls']);
        $subject = "Update on Your Attendance Request for " . Carbon::parse($dateRequested)->format('d M Y');
        $body = "
            <html>
            <body>
                <p>Hi " . $name . ",</p>
                <p>Your attendance request has been reviewed for <strong>" . Carbon::parse($dateRequested)->format('d M Y') . "</strong>. The status is now: <strong>" . $status . "</strong>.</p>
                <p>If you have any questions or need further clarification, please reach out to your manager.</p>
                <p>Thank you!<br>Sphere HRMS</p>
            </body>
            </html>
        ";

        Mail::send([], [], function ($message) use ($email, $subject, $body) {
            $message->from('spherehrms@apisod.ai', 'HRMS Portal')
                    ->to($email)
                    ->subject($subject)
                    ->setBody($body, 'text/html');
        });
    }
    private function calculateLeaveDays($fromDate, $toDate)
    {
        $start = \Carbon\Carbon::parse($fromDate);
        $end = \Carbon\Carbon::parse($toDate);

        return $start->diffInDays($end) + 1; // +1 to include the start date
    }
    private function createEventAfterAttendanceCreate($attendanceRequest)
    {
        $event = new Event();
        $event->EmployeeID = $attendanceRequest->EmployeeID;
        $event->EventType = 'Attendance';
        $event->EventName = 'Clock In';
        $event->EventStartDate = $attendanceRequest->RequestDate;
        $event->EventEndDate = $attendanceRequest->RequestDate;
        $event->Description = 'Mark as present by admin';
        $event->save();
    }
    public function remainingLeavs(Request $request)
    {
        $today = Carbon::today(); // Get today's date
        $request->validate([
            'EmployeeID' => 'required',
            'LeaveType' => 'required'
        ]);
        $getBalanceLeave = EmployeeLeaveBalance::where("EmployeeID",  $request->EmployeeID)->where('Year', $today->format('Y'))->first();
        $leaves = Leave::where('EmployeeID', $request->EmployeeID)
            ->whereIn('Status', ['Approved', 'deducted']) // Add 'deducted' status check
            ->whereYear('StartDate', '=', $today->format('Y')) // Use current year
            ->where('LeaveType', $request->LeaveType)
            ->get();

        $leaveCount = 0;
        foreach ($leaves as $leave) {
            $leaveCount += $this->calculateLeaveDays($leave->StartDate, $leave->EndDate);
        }

        return response()->json(['remainingLeave' => $getBalanceLeave[$request->LeaveType] - $leaveCount, 'status' => 200]);
    }
}
