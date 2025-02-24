<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
use App\Models\Event;
use App\Models\Leave; 
use App\Models\Setting;
use App\Models\Notification;
use Carbon\Carbon;

class CheckClockInStatus extends Command
{
    protected $signature = 'check:clockin-status';
    protected $description = 'Get list of employees who have not clocked in today';

    public function handle()
    {
        $today = Carbon::today();
        $dayOfWeek = strtolower($today->format('l'));
        $isHoliday = Event::where('EventType', 'Holiday')
        ->where('EventStartDate', '<=', $today)
        ->where('EventEndDate', '>=', $today)
        ->exists();
        $settings = Setting::where("type", "working days")->first();
        $setting = json_decode($settings["value"], true);
        $isWorkingDay = $setting && isset($setting[$dayOfWeek]) ? $setting[$dayOfWeek] : false;
        if (!$isHoliday && $isWorkingDay) {
            $employeesNotClockedIn = Employee::whereNotIn('EmployeeID', function ($query) use ($today) {
                $query->select('EmployeeID')
                    ->from('events')
                    ->whereDate('EventStartDate', $today)
                    ->where('EventType', 'Attendance');
            })->where('RoleID', '!=', 1)->get();
            foreach ($employeesNotClockedIn as $employee) {
                $onLeave = Leave::where('EmployeeID', $employee->EmployeeID) ->where('Status', 'Approved')
                ->where(function ($query) use ($today) {
                    $query->whereDate('StartDate', '<=', $today)
                            ->whereDate('EndDate', '>=', $today);
                })->exists();
                if(!$onLeave){
                    $notification = new Notification();
                    $notification->EmployeeID = $employee->EmployeeID;
                    $notification->Title = 'Attendance Alert';
                    $notification->Message = 'Reminder: You have not clocked in on ' . $today->format('D, j M Y') . ', hence your leave has been deducted. Please raise attendance request.';
                    $notification->IsRead = 0;
                    $notification->save();
                    
                    // Create a new leave entry
                    $leave = new Leave();
                    $leave->EmployeeID = $employee->EmployeeID;
                    $leave->LeaveType = "Paid";
                    $leave->HalfFull = "Full Day";
                    $leave->StartDate = $today;
                    $leave->EndDate = $today;
                    $leave->Status = 'Deducted';
                    $leave->Description = 'Automatic deduction for not clocking in';
                    $leave->save();
                }
            }
        }
        $this->info('Checked clock-in status for today.');
    }
}
