<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Models\Smtp;
class EventController extends Controller
{
    public function getCalenderData(Request $request){
        $employeeId = $request->EmployeeID;
        $events = Event::when($employeeId, function ($query, $employeeId) {
            $query->where(function ($query) use ($employeeId) {
                $query->where('EmployeeID', $employeeId)
                      ->orWhereNull('EmployeeID');
            });
        })->with('employee')->get();
    
        $eventsWithEmployeeFirstName = $events->map(function ($event) {
            $event->EmployeeFirstName = $event->employee ? $event->employee->FirstName : null;
            return $event;
        });
    
        return response()->json(['events' => $eventsWithEmployeeFirstName], 200);
    }
    
    public function addEvent(Request $request){
        $request->validate([
            'EventType' => 'required',
            'EventName' => 'required',
            'StartDate' => 'required',
            'EndDate' => 'required'
        ]);
        $event = new Event();
        $event->EmployeeID = $request->input('EmployeeID')?$request->input('EmployeeID'):NULL;
        $event->EventType = $request->input('EventType');
        $event->EventName = $request->input('EventName');
        $event->EventStartDate = $request->input('StartDate');
        $event->EventEndDate = $request->input('EndDate');
        $event->Description = $request->input('Description');
        $event->save();

        // Send email notification if EventType is "Meeting" or EmployeeID is null
        if ($event->EventType == "Meeting" && is_null($event->EmployeeID)) {
            $this->sendMeetingNotification($event);
        }
        if ($event->EventType == "Holiday") {
            $this->sendHolidayNotification($event);
        }
        return response()->json(['message' => 'Event has been successfully created.'], 201);
    }

    private function sendMeetingNotification($event)
    {
        $subject = "New Calendar Event: " . $event->EventName;
        $body = "
            <html>
            <body>
                <p>Hi,</p>
                <p>A new event has been added to your calendar:</p>
                <p><strong>Event Name:</strong> " . $event->EventName . "</p>
                <p><strong>Date & Time:</strong> " . Carbon::parse($event->EventStartDate)->format('d M Y') . "</p>
                <p><strong>Description:</strong> " . $event->Description . "</p>
                <p>Please mark your calendar and let us know if you have any questions or conflicts regarding this event.</p>
                <p>Thank you!<br>Sphere HRMS</p>
            </body>
            </html>
        ";

        $employees = Employee::get();
        $smtp = Smtp::first();
        config(['mail.mailers.smtp.host' => $smtp->host]);
        config(['mail.mailers.smtp.port' => $smtp->port]);
        config(['mail.mailers.smtp.username' => $smtp->username]);
        config(['mail.mailers.smtp.password' => $smtp->password]);
        config(['mail.mailers.smtp.encryption' => 'tls']);
        foreach ($employees as $employee) {
            Mail::send([], [], function ($message) use ($subject, $body, $employee) {
                $message->from('spherehrms@apisod.ai', 'HRMS Portal')
                        ->to($employee->email) // Use employee's email
                        ->subject($subject)
                        ->setBody($body, 'text/html');
            });
        }
    }
     private function sendHolidayNotification($event)
    {
        $subject = "Holiday Notification";
        $body = "
            <html>
            <body>
                <p>Hi [User's Name],</p>
                <p>A holiday has been added to the calendar:</p>
                <p><strong>Holiday Name & Date:</strong> " . $event->EventName . " " . Carbon::parse($event->EventStartDate)->format('d M Y') . "</p>
                <p>Please note this holiday and enjoy your time off! If you have any questions, feel free to reach out to the HR team.</p>
                <p>Thank you!<br>Sphere HRMS</p>
            </body>
            </html>
        ";

        $employees = Employee::get();
        $smtp = Smtp::first();
        config(['mail.mailers.smtp.host' => $smtp->host]);
        config(['mail.mailers.smtp.port' => $smtp->port]);
        config(['mail.mailers.smtp.username' => $smtp->username]);
        config(['mail.mailers.smtp.password' => $smtp->password]);
        config(['mail.mailers.smtp.encryption' => 'tls']);
        foreach ($employees as $employee) {
            $personalizedBody = str_replace("[User's Name]", $employee->FirstName, $body);
            Mail::send([], [], function ($message) use ($subject, $personalizedBody, $employee) {
                $message->from('spherehrms@apisod.ai', 'HRMS Portal')
                        ->to($employee->email) // Use employee's email
                        ->subject($subject)
                        ->setBody($personalizedBody, 'text/html');
            });
        }
    }
}
