<!DOCTYPE html>
<html>
<head>
    <title>Interview Schedule</title>
</head>
<body>
    <p>Dear {{ $interviewSchedule->employeeName }},</p>
    <p>We are pleased to inform you that an interview has been scheduled with the following details:</p>
    <p>Candidate Details:</p>
    <ul>
        <li>Name: {{ $interviewSchedule->candidateName }}</li>
        <li>Position: {{ $interviewSchedule->position }}</li>
        <li>Email: {{ $interviewSchedule->email }}</li>
        <li>Mobile: {{ $interviewSchedule->mobile }}</li>
    </ul>
    <p>Interview Details:</p>
    <ul>
        <li>Round: {{ $interviewSchedule->round }}</li>
        <li>Date: {{ $interviewSchedule->date }}</li>
        <li>Meeting Type: {{ $interviewSchedule->meetingType }}</li>
        @if($interviewSchedule->meetingLink)
            <li>Meeting Link: {{ $interviewSchedule->meetingLink }}</li>
        @endif
    </ul>
    <p>Best regards,</p>
    <p>APISOD</p>
</body>
</html>