<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use Carbon\Carbon;
use App\Models\Employee;
use App\Models\EmployeeLeaveBalance;
use App\Models\Leave;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
class ReportController extends Controller
{
    public function attendance(Request $request)
    {
        $request->validate([
            'year' => 'required|integer',
            'month' => 'required|integer|between:1,12',
        ]);
        
        $year = $request->input('year');
        $month = $request->input('month');
        $employees = Employee::select('EmployeeID', 'CompanyID', 'FirstName', 'LastName', 
            DB::raw("CONCAT(FirstName, ' ', LastName) as Name"))
            ->where("RoleID", "!=", "1")->get();
        $holidays = Event::where('EventType', 'Holiday')
            ->whereYear('EventStartDate', $year)
            ->whereMonth('EventStartDate', $month)
            ->get();
        if ($request->input('EmployeeID') == "") {
            $events = Event::with(['employee'])
                ->where('EventType', 'Attendance')
                ->whereYear('EventStartDate', $year)
                ->whereMonth('EventStartDate', $month)
                ->get();
            $leaves = Leave::where('Status', 'Approved')
            ->where(function($query) use ($year, $month) {
                $query->whereYear('StartDate', $year)
                      ->whereMonth('StartDate', $month)
                      ->orWhere(function($subQuery) use ($year, $month) {
                          $subQuery->whereYear('EndDate', $year)
                                   ->whereMonth('EndDate', $month);
                      });
            })
            ->get();
        } else {
            $events = Event::with(['employee'])
                ->where('EventType', 'Attendance')
                ->whereYear('EventStartDate', $year)
                ->whereMonth('EventStartDate', $month)
                ->where('EmployeeID', $request->input('EmployeeID'))
                ->get();
                $leaves = Leave::where('Status', 'Approved')
                ->where('EmployeeID', $request->input('EmployeeID'))
                ->where(function($query) use ($year, $month) {
                    $query->whereYear('StartDate', $year)
                          ->whereMonth('StartDate', $month)
                          ->orWhere(function($subQuery) use ($year, $month) {
                              $subQuery->whereYear('EndDate', $year)
                                       ->whereMonth('EndDate', $month);
                          });
                })
                ->get();
        }

        $report = [];
        $daysInMonth = Carbon::createFromDate($year, $month)->daysInMonth;
        $currentDate = Carbon::now();
        $lastDay = ($currentDate->month == $month && $currentDate->year == $year) ? $currentDate->day : $daysInMonth;

        // Process attendance events and build the report
        foreach ($events as $event) {
            
            $eventDate = Carbon::parse($event->EventStartDate);
            $userName = $event->employee->FirstName . " " . $event->employee->LastName; 

            if (!isset($report[$userName])) {
                $report[$userName] = [
                    'id' =>  $event->employee->EmployeeID,
                    'name' => $userName,
                    'EmployeeID' => $event->employee->CompanyID,
                    'report' => array_fill(1, $daysInMonth, '-'), 
                ];
            }
            if ($eventDate->isBefore($currentDate)) {
                $report[$userName]['report'][$eventDate->day] = 'P'; 
            }
        }
        $leaveData = [];
        $holidayData = [];
        foreach ($leaves as $leave) {
            $startDate = Carbon::parse($leave->StartDate);
            $endDate = Carbon::parse($leave->EndDate);
            $employeeID = $leave->EmployeeID;
            $leaveType = $leave->LeaveType;
            for ($date = $startDate; $date->lte($endDate); $date->addDay()) {

                $leaveData[] = [
                    'EmployeeID' => $employeeID,
                    'Date' => $date->day,
                    'LeaveType' => $this->getAbbreviation($leaveType)
                ];
            }
        }
        foreach ($leaveData as $leave) {
            $employeeID = $leave['EmployeeID'];
            $date = $leave['Date'];
        
            foreach ($report as $userName => &$userData) {
                if ($userData['id'] == $employeeID) {
                    $userData['report'][$date] = $leave['LeaveType'];
                }
            }
        }
        foreach($holidays as $holiday){
            $startDate = Carbon::parse($holiday->EventStartDate);
            $endDate = Carbon::parse($holiday->EventEndDate);
            for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
                $holidayData[] = [
                    'Date' => $date->day
                ];
            }
        }
        foreach ($holidayData as $holiday) {
            $date = $holiday['Date'];
            foreach ($report as $userName => &$userData) {
                //if ($userData['report'][$date] === '-') { // Check if the date is not marked yet
                    $userData['report'][$date] = 'H'; // Mark it as a holiday
                //}
            }
        }
        // Mark absent days for each employee in the report
        foreach ($report as &$userReport) {
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $currentDay = Carbon::createFromDate($year, $month, $day);
                // Only mark absent if the day is in the past and not a Saturday or Sunday
                if ($day <= $lastDay) {
                    if ($userReport['report'][$day] === '-' && $currentDay->isBefore($currentDate) && !in_array($currentDay->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY])) {
                        $userReport['report'][$day] = 'A';
                    }
                } else {
                    $userReport['report'][$day] = '-';
                }
            }
        }

        // Include employees without attendance records
        if($request->input('EmployeeID') == ""){
            foreach ($employees as $employee) {
                $employeeName = $employee->FirstName . " " . $employee->LastName;

                if (!isset($report[$employeeName])) {
                    $report[$employeeName] = [
                        'id' => $employee->EmployeeID,
                        'name' => $employeeName,
                        'EmployeeID' => $employee->CompanyID,
                        'report' => array_fill(1, $daysInMonth, '-'), // No records, so all days are '-'
                    ];
                }
            }
        }
        // Sort the report by employee name
        $formattedReport = array_values($report);
        usort($formattedReport, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        return response()->json(["data" => $formattedReport, "employees" => $employees, "status" => 200]);
    }
    function getAbbreviation($value) {
        $abbreviations = [
            "Paid" => "PL",
            "Floating" => "FL",
            "Sick" => "SL",
            "Bereavement" => "BL",
            "Paternity" => "PL",
            "Maternity" => "ML"
        ];
        return $abbreviations[$value] ?? "A"; // Return "Unknown" for unexpected values
    }
    public function leave(Request $request)
    {
        $request->validate([
            'StartDate' => 'required|date',
            'EndDate' => 'required|date|after_or_equal:StartDate',
        ]);

        // Get all employees excluding those with RoleID = 1
        $employees = Employee::select('EmployeeID', 'CompanyID', 'Gender', 'FirstName', 'LastName', 
            DB::raw("CONCAT(FirstName, ' ', LastName) as Name"))
            ->where("RoleID", "!=", "1")
            ->get();

        // Get leave counts, filtering by EmployeeID if provided
        $leaveCounts = Leave::with(['employee'])
            ->select('EmployeeID', 'LeaveType', DB::raw('count(*) as total_count'))
            ->whereIn('Status', ['Approved', 'deducted'])
            ->when($request->input('EmployeeID'), function($query) use ($request) {
                return $query->where('EmployeeID', $request->input('EmployeeID'));
            })
            ->groupBy('EmployeeID', 'LeaveType')
            ->get();

        // Get leave balances for the current year
        $year = date('Y');
        $leaveBalances = EmployeeLeaveBalance::where('Year', $year)
            ->get()
            ->keyBy('EmployeeID');

        $formattedData = [];

        foreach ($employees as $employee) {
            // Initialize each employee's data
            $formattedData[$employee->EmployeeID] = [
                'EmployeeID' => $employee->EmployeeID,
                'CompanyID' => $employee->CompanyID,
                'Gender' => $employee->Gender,
                'EmployeeName' => $employee->FirstName . " " . $employee->LastName,
                'ProfilePhoto' => $employee->ProfilePicture ? url(Storage::url('profile_pictures/' . $employee->ProfilePicture)) : url(Storage::url('profile_pictures/no-img.jpg')),
            ];

            // Initialize leave types and remaining balances
            $leaveTypes = ['Paid', 'Sick', 'Floating', 'Bereavement', 'Paternity', 'Maternity'];
            foreach ($leaveTypes as $type) {
                $formattedData[$employee->EmployeeID][$type] = 0; // Default count to 0
                $remainingKey = 'Remaining' . $type;
                $formattedData[$employee->EmployeeID][$remainingKey] = $leaveBalances[$employee->EmployeeID]->$type ?? 0;
            }
        }

        // Populate leave counts into the formatted data
        foreach ($leaveCounts as $leave) {
            $employeeId = $leave->EmployeeID;
            $leaveType = $leave->LeaveType;
            $totalCount = $leave->total_count;

            // Increment the leave count for the specific type
            $formattedData[$employeeId][$leaveType] += $totalCount;

            // Update the remaining leaves
            $remainingKey = 'Remaining' . $leaveType;
            if (isset($formattedData[$employeeId][$remainingKey])) {
                $formattedData[$employeeId][$remainingKey] -= $totalCount; // Subtract the total taken leave
            }
        }

        // Filter the formatted data by EmployeeID if provided
        if ($request->input('EmployeeID')) {
            $formattedData = array_filter($formattedData, function($data) use ($request) {
                return $data['EmployeeID'] == $request->input('EmployeeID');
            });

            $employees = $employees->filter(function($employee) use ($request) {
                return $employee->EmployeeID == $request->input('EmployeeID');
            })->values();
        }

        $formattedData = array_values($formattedData);
        return response()->json(["data" => $formattedData, "employees"=> $employees, "status" => 200]);
    }

    public function employee(Request $request)
    {
        $employees = Employee::with(['role', 'department', 'manager'])->get()->toArray();
        foreach ($employees as &$employee) {
            $employee['CompanyID'] = $employee['CompanyID']?$employee['CompanyID']:"-";
            $employee['ProfilePicture'] = $employee['ProfilePicture']?url(Storage::url('profile_pictures/' . $employee['ProfilePicture'])):url(Storage::url('profile_pictures/no-img.jpg')); // update the ProfilePicture key
        }
        return response()->json(['data'=> $this->removePasswordKey($employees), 'status' => 200]);
    }
    function removePasswordKey($employees) {
        return array_map(function ($employee) {
            unset($employee['password']);
            return $employee;
        }, $employees);
    }
}
