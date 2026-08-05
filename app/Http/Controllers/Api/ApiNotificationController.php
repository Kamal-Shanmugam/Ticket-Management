<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApiNotificationController extends Controller
{
    use HandlesApiResponses;

    /**
     * List current user's notifications.
     */
    public function index(Request $request)
    {
        $user = Auth::guard('employee')->user() ?: Auth::guard('customer')->user() ?: Auth::user();

        if (!$user) {
            return $this->errorResponse('Unauthenticated.', [], 401);
        }

        $query = $user->notifications();

        if ($request->has('unread') && filter_var($request->unread, FILTER_VALIDATE_BOOLEAN)) {
            $query = $user->unreadNotifications();
        }

        $notifications = $query->paginate(15);

        return $this->successResponse(
            $notifications->toArray(),
            'Notifications retrieved successfully.'
        );
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead($id)
    {
        $user = Auth::guard('employee')->user() ?: Auth::guard('customer')->user() ?: Auth::user();

        if (!$user) {
            return $this->errorResponse('Unauthenticated.', [], 401);
        }

        $notification = $user->notifications()->where('id', $id)->first();

        if (!$notification) {
            return $this->errorResponse('Notification not found.', [], 404);
        }

        $notification->markAsRead();

        return $this->successResponse(null, 'Notification marked as read.');
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead()
    {
        $user = Auth::guard('employee')->user() ?: Auth::guard('customer')->user() ?: Auth::user();

        if (!$user) {
            return $this->errorResponse('Unauthenticated.', [], 401);
        }

        $user->unreadNotifications->markAsRead();

        return $this->successResponse(null, 'All notifications marked as read.');
    }
}
