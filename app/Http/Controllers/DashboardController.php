<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Event;
use App\Models\LeaveBalance;
use App\Models\EmployeeLeaveBalance;
use App\Models\Leave;
use Carbon\Carbon; // Add this line to use Carbon for date manipulation
use Illuminate\Support\Facades\DB;
class DashboardController extends Controller
{
    public function getEmployeeDashboard(Request $request)
    {
        $today = Carbon::today(); // Get today's date
        $attendance = Attendance::where("EmployeeID", $request->EmployeeID)
                              ->whereDate("PunchInTime", $today)
                              ->latest('PunchInTime')
                              ->first();

        // Check if the employee is on leave today and if it's a half-day or full-day leave
        $leaveToday = Leave::where('EmployeeID', $request->EmployeeID)
            ->where('Status', 'Approved')
            ->whereDate('StartDate', '<=', $today)
            ->whereDate('EndDate', '>=', $today)
            ->first();

        $isOnLeaveToday = $leaveToday ? true : false;
        $leaveType = $leaveToday ? $leaveToday->HalfFull : null;
        $halfFullType = ($leaveToday && $leaveToday->HalfFull === 'Half Day') ? $leaveToday->HalfDayType : null;
        $employeeCanLoginAfterLunch = ($halfFullType === 'First Half') ? true : false;

        if ($attendance && $attendance->PunchOutTime === null) {
            $response = [
                'status' => 'Clocked in',
                'PunchInTime' => Carbon::parse($attendance->PunchInTime)->format('h:i A')
            ];
        } else {
            $response = [
                'status' => 'Not clocked in yet'                
            ];
        }
        $birthdays = Employee::whereMonth('DateOfBirth', $today->month)->whereDay('DateOfBirth', $today->day)->get();
        $workAnniversaries = Employee::whereMonth('DateOfJoining', $today->month)->whereDay('DateOfJoining', $today->day)->get();
        $events = Event::whereDate('EventStartDate', '>=', $today->format('Y-m-d'))
        ->whereDate('EventEndDate', '<=', $today->format('Y-m-d'))
        ->where('EventType', '!=', "Attendance")->get();
        $leaves = Event::whereDate('EventStartDate', '>=', $today->format('Y-m-d'))
        ->whereIn('EventType', ['Holiday', 'Holiday-F', 'Leaves'])->get();

        $todaysLeave = Leave::with(['employee'])->whereDate('StartDate', '<=', $today->format('Y-m-d'))
        ->whereDate('EndDate', '>=', $today->format('Y-m-d'))
        ->where('Status', '=', "Approved")->get();
        
        $upcomingLeave = Leave::with(['employee'])->whereDate('StartDate', '>', $today->format('Y-m-d'))
        ->where('Status', '=', "Approved")->get();
        $takenPaidLeave = 0;
        $leaveBalance = EmployeeLeaveBalance::select('Paid')->where("EmployeeID", $request->EmployeeID)->where('Year', $today->format('Y'))->first();
        //print_r($leaveBalance);
        /*$leaveCounts = Leave::with(["employee"])
            ->select('EmployeeID', 'LeaveType', 'HalfFull', 'StartDate', 'EndDate', DB::raw('count(*) as total_count'))
            ->where('EmployeeID', $request->input('EmployeeID'))
            ->whereIn('Status', ['Approved', 'deducted']) // Add 'deducted' status check
            ->groupBy('EmployeeID', 'LeaveType', 'HalfFull', 'StartDate', 'EndDate')
            ->get();*/
        $leaveCounts = Leave::with(["employee"])
            ->select('EmployeeID', 'LeaveType', 'HalfFull', 'StartDate', 'EndDate', DB::raw('count(*) as total_count'))
            ->where('EmployeeID', $request->input('EmployeeID'))
            ->whereIn('Status', ['Approved', 'deducted']) // Add 'deducted' status check
            ->whereYear('StartDate', '=', $today->format('Y')) // Use current year
            ->groupBy('EmployeeID', 'LeaveType', 'HalfFull', 'StartDate', 'EndDate')
            ->get();
        foreach ($leaveCounts as $leave) {
            $daysCount = Carbon::parse($leave->StartDate)->diffInDays(Carbon::parse($leave->EndDate)) + 1;
            if ($leave->LeaveType === 'Paid') {
                $takenPaidLeave += $leave->HalfFull === 'Half Day' ? 0.5 * $daysCount : $daysCount;
            }
        }
        //$leaveBalance->RemainingLeave = $leaveBalance && $leaveBalance->Paid ? $this->formatValue($leaveBalance->Paid - $takenPaidLeave):0;
        //$leaveBalance->TotalLeave = $this->formatValue($leaveBalance->TotalLeave);
        //$leaveBalance->UsedLeave = $this->formatValue($takenPaidLeave);
        if ($leaveBalance) {
            $leaveBalance->RemainingLeave = $leaveBalance->Paid ? $this->formatValue($leaveBalance->Paid - $takenPaidLeave) : 0;
            $leaveBalance->UsedLeave = $this->formatValue($takenPaidLeave);
        } else {
            $leaveBalance = (object) [
                'RemainingLeave' => 0,
                'UsedLeave' => $this->formatValue($takenPaidLeave)
            ];
        }
        $todaysActivity = [];
        $leavesActivity = [];
        foreach ($todaysLeave as $key => $value) {
            $todaysActivity[] = [
                'type' => '',
                'message' => ($value->HalfFull == "Full Day")
                ?($request->EmployeeID == $value->employee->EmployeeID ? "Today, you are on leave." : "Today, ".$value->employee->FirstName." is on leave.")
                :($request->EmployeeID == $value->employee->EmployeeID ? "Today, you are on ".($value->HalfDayType?strtolower($value->HalfDayType):"half")." day leave.":$value->employee->FirstName." is on ".($value->HalfDayType?strtolower($value->HalfDayType):"half")." day leave."),
                
            ];
        }
        foreach ($birthdays as $key => $value) {
            $todaysActivity[] = [
                'type' => 'birthday',
                'message' => "Keep wishes flowing, it's $value->FirstName's birthday.",
                'employeeId' => $value->EmployeeID
            ];
        }
        foreach ($workAnniversaries as $key => $value) {
            $todaysActivity[] = [
                'type' => 'anniversary',
                'message' => "Keep wishes flowing, it's $value->FirstName's work Anniversary.",
                'employeeId' => $value->EmployeeID
            ];
        }
        foreach ($events as $key => $value) {
            if($value->EventType === "Meeting"){
                $todaysActivity[] = [
                    'type' => 'Meeting',
                    'message' => "$value->EventName",
                    'date' => Carbon::parse($value->EventStartDate)->format('h:m A')
                ];
            }
        }
        foreach ($leaves as $key => $value) {
            $date = Carbon::parse($value->EventStartDate)->format('D, d M Y');
            if ($value->EventStartDate != $value->EventEndDate) {
                $date .= ' - ' . Carbon::parse($value->EventEndDate)->format('D, d M Y');
            }
            $eventName = $value->EventType === "Holiday-F" ? $value->EventName . " (Floating)" : $value->EventName;
            $leavesActivity[] = [
                'type' => $value->EventType,
                'message' => $eventName,
                'date' => $date,
                'start_date' => Carbon::parse($value->EventStartDate)->format('D, d M Y')
            ];
        }
        foreach ($upcomingLeave as $key => $value) {
            $date = Carbon::parse($value->StartDate)->format('D, d M Y');
            if ($value->StartDate != $value->EndDate) {
                $date .= ' - ' . Carbon::parse($value->EndDate)->format('D, d M Y');
            }
            $leavesActivity[] = [
                'type' => 'leave',
                'message' => ($value->HalfFull == "Full Day")?$value->employee->FirstName." will be on leave.":$value->employee->FirstName." will be on ".($value->HalfDayType?strtolower($value->HalfDayType):"half")." day leave.",
                'date' => $date,
                'start_date' => Carbon::parse($value->StartDate)->format('D, d M Y')
            ];
        }
        usort($leavesActivity, function($a, $b) {
            return strtotime($a['start_date']) <=> strtotime($b['start_date']);
        });
        return response()->json(["result" => 
        [
            "clockedInData" => $response,
            "todaysEvent"=>count($birthdays)+count($workAnniversaries)+count($events),
            "todaysActivity" => $todaysActivity,
            "leavesActivity" => $leavesActivity,
            "leaveBalance" => $leaveBalance,
            "isOnLeaveToday" => $isOnLeaveToday,
            "employeeCanLoginAfterLunch" => $employeeCanLoginAfterLunch
        ], 'status' => 200]);
    }
    public function getAdminDashboard(Request $request)
    {
        $today = Carbon::today();
        $activeEmployeesCount = Employee::where('Status', "Active")->count();
        $todayClockedInCount = Attendance::whereDate("PunchInTime", $today)->whereNull("PunchOutTime")->count();
        $birthdays = Employee::whereMonth('DateOfBirth', $today->month)->whereDay('DateOfBirth', $today->day)->get();
        $workAnniversaries = Employee::whereMonth('DateOfJoining', $today->month)->whereDay('DateOfJoining', $today->day)->get();
        $events = Event::whereDate('EventStartDate', '>=', $today->format('Y-m-d'))
        ->whereDate('EventEndDate', '<=', $today->format('Y-m-d'))
        ->where('EventType', '!=', "Attendance")->get();
        
        $todaysLeave = Leave::with(['employee'])->whereDate('StartDate', '<=', $today->format('Y-m-d'))
        ->whereDate('EndDate', '>=', $today->format('Y-m-d'))
        ->where('Status', '=', "Approved")->get();

        $leaves = Event::whereDate('EventStartDate', '>=', $today->format('Y-m-d'))
        ->whereIn('EventType', ['Holiday', 'Holiday-F', 'Leaves'])->get();

        $upcomingLeave = Leave::with(['employee'])->whereDate('StartDate', '>', $today->format('Y-m-d'))
        ->where('Status', '=', "Approved")->get();


        $todaysActivity = [];
        $leavesActivity = [];
        foreach ($todaysLeave as $key => $value) {
            $todaysActivity[] = [
                'type' => 'employee-leave',
                'message' => "Today, ".$value->employee->FirstName." is on leave.",
                'employeeId' => $value->employee->EmployeeID
            ];
        }
        foreach ($birthdays as $key => $value) {
            $todaysActivity[] = [
                'type' => 'birthday',
                'message' => "Keep wishes flowing, it's $value->FirstName's birthday.",
                'employeeId' => $value->EmployeeID
            ];
        }
        foreach ($workAnniversaries as $key => $value) {
            $todaysActivity[] = [
                'type' => 'anniversary',
                'message' => "Keep wishes flowing, it's $value->FirstName's work Anniversary.",
                'employeeId' => $value->EmployeeID
            ];
        }
        foreach ($events as $key => $value) {
            if($value->EventType === "Meeting"){
                $todaysActivity[] = [
                    'type' => 'Meeting',
                    'message' => "$value->EventName",
                    'date' => Carbon::parse($value->EventStartDate)->format('h:m A')
                ];
            }
        }
        foreach ($leaves as $key => $value) {
            $date = Carbon::parse($value->EventStartDate)->format('D, d M Y');
            if ($value->EventStartDate != $value->EventEndDate) {
                $date .= ' - ' . Carbon::parse($value->EventEndDate)->format('D, d M Y');
            }
            $eventName = $value->EventType === "Holiday-F" ? $value->EventName . " (Floating)" : $value->EventName;
            $leavesActivity[] = [
                'type' => $value->EventType,
                'message' => $eventName,
                'date' => $date,
                'start_date' => Carbon::parse($value->EventStartDate)->format('D, d M Y')
            ];
        }
        foreach ($upcomingLeave as $key => $value) {
            $date = Carbon::parse($value->StartDate)->format('D, d M Y');
            if ($value->StartDate != $value->EndDate) {
                $date .= ' - ' . Carbon::parse($value->EndDate)->format('D, d M Y');
            }
            $leavesActivity[] = [
                'type' => 'leave',
                'message' => ($value->HalfFull == "Full Day")?$value->employee->FirstName." will be on leave.":$value->employee->FirstName." will be on ".($value->HalfDayType?strtolower($value->HalfDayType):"half")." day leave.",
                'date' => $date,
                'start_date' => Carbon::parse($value->StartDate)->format('D, d M Y')
            ];
        }
        usort($leavesActivity, function($a, $b) {
            return strtotime($a['start_date']) <=> strtotime($b['start_date']);
        });
        return response()->json(["result" => 
            [
                "employeesWidget" => $todayClockedInCount."/".$activeEmployeesCount, 
                "leaveWidget" => count($todaysLeave),
                "todaysEvent"=>count($birthdays)+count($workAnniversaries)+count($events),
                "todaysActivity" => $todaysActivity,
                "leavesActivity" => $leavesActivity
            ], 'status' => 200]);
    }
    private function formatValue($value) {
        return floor($value) == $value ? (int) $value : $value;
    }
}