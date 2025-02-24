<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Event;
use Carbon\Carbon;
class AttendanceController extends Controller
{
    public function markAttendance(Request $request){
        $data = $request->only('EmployeeID', 'AttendanceTime');
        $request->validate([
            'EmployeeID' => 'required',
            'AttendanceTime' => 'required'
        ]);
        $today = Carbon::today();
        $attendance = Attendance::where('EmployeeID', $data['EmployeeID'])->whereDate("PunchInTime", $today)->whereNull('PunchOutTime')->first();

        if ($attendance) {
            $attendance->PunchOutTime = $data['AttendanceTime'];
            $attendance->save();
            $this->createEventAfterAttendanceUpdate($data);
            return response()->json(['message' => 'You have successfully clocked out.'], 200);
        } else {
            $attendance = new Attendance();
            $attendance->EmployeeID = $data['EmployeeID'];
            $attendance->PunchInTime = $data['AttendanceTime'];
            $attendance->save();
            $this->createEventAfterAttendanceCreate($data);
            return response()->json(['message' => 'You have successfully clocked in.'], 200);
        }
    }

    private function createEventAfterAttendanceCreate($data)
    {
        $event = new Event();
        $event->EmployeeID = $data['EmployeeID'];
        $event->EventType = 'Attendance';
        $event->EventName = 'Clock In';
        $event->EventStartDate = $data['AttendanceTime'];
        $event->EventTime = Carbon::parse($data['AttendanceTime'])->format('H:i:s'); // Corrected syntax
        $event->EventEndDate = $data['AttendanceTime'];
        $event->Description = 'clocked in at ' . Carbon::parse($data['AttendanceTime'])->format('h:i A');
        $event->save();
    }

    private function createEventAfterAttendanceUpdate($data)
    {
        $event = new Event();
        $event->EmployeeID = $data['EmployeeID'];
        $event->EventType = 'Attendance';
        $event->EventName = 'Clock Out';
        $event->EventStartDate = $data['AttendanceTime'];
        $event->EventTime = Carbon::parse($data['AttendanceTime'])->format('H:i:s'); // Corrected syntax
        $event->EventEndDate = $data['AttendanceTime'];
        $event->Description = 'clocked out at ' . Carbon::parse($data['AttendanceTime'])->format('h:i A');
        $event->save();
    }
}
