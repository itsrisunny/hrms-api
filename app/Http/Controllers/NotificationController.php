<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
class NotificationController extends Controller
{
    public function getNotification(Request $request)
    {
        $request->validate([
            'EmployeeID' => 'required',
        ]);
        
        $notifications = Notification::with(['employee'])->where('EmployeeID', $request->EmployeeID)
            ->orderBy('CreatedAt', 'desc') 
            ->get();
        $formattedNotifications = $notifications->map(function ($notification) {
            $utcTime = Carbon::now('UTC');
            $indianTime = $utcTime->setTimezone('Asia/Kolkata');
            $now  = $indianTime->format('Y-m-d H:i:s'); 
            $createdAt = Carbon::parse($notification->CreatedAt);
            $timeAgo = $createdAt->diffForHumans($now);
            return [
                'NotificationID' => $notification->NotificationID,
                'Title' => $notification->Title,
                'Message' => $notification->Message,
                'IsRead' => $notification->IsRead,
                'time_ago' => $timeAgo,
                'ProfilePhoto' =>  $notification->employee->ProfilePicture?url(Storage::url('profile_pictures/' . $notification->employee->ProfilePicture)):url(Storage::url('profile_pictures/no-img.jpg'))
            ];
        });
        return response()->json(["result" => $formattedNotifications, 'status' => 200]);
    }
    public function readNotification(Request $request)
    {
        $request->validate([
            'NotificationID' => 'required',
        ]);
        $notification = Notification::find($request->NotificationID);
        if ($notification->IsRead == 0) {
            $notification->IsRead = 1;
            $notification->save();
            return response()->json(["message" => "Notification marked as read.", 'status' => 200]);
        }else{
            return response()->json(["message" => "This notification has already been read.", 'status' => 400]);
        }
    }
}