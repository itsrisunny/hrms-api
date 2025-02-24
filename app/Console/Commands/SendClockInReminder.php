<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
use App\Models\Event;
use App\Models\Leave;
use App\Models\Setting;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Models\Smtp;

class SendClockInReminder extends Command
{
    protected $signature = 'send:clockin-reminder';
    protected $description = 'Send email reminders to employees who have not clocked in today';

    public function handle()
    {
        $smtp = Smtp::first();
        config(['mail.mailers.smtp.host' => $smtp->host]);
        config(['mail.mailers.smtp.port' => $smtp->port]);
        config(['mail.mailers.smtp.username' => $smtp->username]);
        config(['mail.mailers.smtp.password' => $smtp->password]);
        config(['mail.mailers.smtp.encryption' => 'tls']);
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
            $employees = Employee::where('RoleID', '!=', 1)->get();
            foreach ($employees as $employee) {
                $onLeave = Leave::where('EmployeeID', $employee->EmployeeID)
                    ->where('Status', 'Approved')
                    ->where(function ($query) use ($today) {
                        $query->whereDate('StartDate', '<=', $today)
                            ->whereDate('EndDate', '>=', $today);
                    })->exists();
                if (!$onLeave) {
                    $emailBody = "Hi {$employee->FirstName},\n\nThis is a gentle reminder to clock in for the day. Please ensure you clock in within the next 15 minutes to avoid missing your time limit.\nIf you've already clocked in, kindly disregard this message.\n\nThank you!\nSphere HRMS";
                    Mail::raw($emailBody, function ($message) use ($employee) {
                        $message->from('spherehrms@apisod.ai', 'HRMS Portal')
                                ->to($employee->email)
                                ->subject('Clock-In Reminder');
                    });
                }
            }
        }
        $this->info('Sent clock-in reminders for today.');
    }
}
