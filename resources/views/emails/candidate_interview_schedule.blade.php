<!DOCTYPE html>
<html>
<head>
    <title>Your Interview Schedule</title>
</head>
<body>
    <h1>Dear {{ $interviewSchedule->candidateName }},</h1>
    <p>We are pleased to inform you that your interview for the position of {{ $interviewSchedule->position }} has been scheduled.</p>
    <p><strong>Interview Details:</strong></p>
    @foreach($interviewSchedule->interviewRounds as $round)
        <ul>
            <li><strong>Round:</strong> {{ $round['round'] }}</li>
            <li><strong>Date:</strong> {{ $round['date'] }}</li>
            <li><strong>Meeting Type:</strong> {{ $round['meetingType'] }}</li>
            @if($round['meetingLink'])
                <li><strong>Meeting Link:</strong> <a href="{{ $round['meetingLink'] }}">{{ $round['meetingLink'] }}</a></li>
            @endif
            <li><strong>Interviewer Name:</strong> {{ $round['employeeName'] }}</li>
            <li><strong>Interviewer Email:</strong> {{ $round['employeeEmail'] }}</li>
        </ul>
    @endforeach
    <p>We wish you all the best for your interview.</p>
    <p>Regards,<br>HRMS Portal Team</p>
</body>
</html>