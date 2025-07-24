<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class NotificationController extends Controller
{
    public function showForUpdating($id, $notification_id)

    {
        $notifications = Notification::find($notification_id)->latest();
        $notifications->status = 'read';
        $notifications->color = 'green';
        $notifications->update();
        return view('home', [
            'notifications' => $notifications
        ]);
    }

    public function read($id)
    {
        $notifications = Notification::find($id);
        $notifications->status = 'read';
            $notifications->color = 'gray';
            $notifications->reciver_id = auth()->user()->id;
            $notifications->update();
        if ($notifications->link == null) {
           
            return Redirect::to('/home');
        } else {return Redirect::to($notifications->link);
        }
            
    }
}




