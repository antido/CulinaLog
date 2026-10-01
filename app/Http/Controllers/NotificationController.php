<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    /**
     * List the signed-in member's notifications, newest first.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('Notifications/Index', [
            'notifications' => $request->user()
                ->notifications()
                ->paginate(20)
                ->through(fn (DatabaseNotification $notification) => [
                    'id' => $notification->id,
                    'message' => $notification->data['message'],
                    'is_read' => $notification->read_at !== null,
                    'received' => $notification->created_at->diffForHumans(),
                ]),
        ]);
    }

    /**
     * Mark one notification as read and go to the page it is about.
     *
     * The notification is looked up among the signed-in member's own
     * notifications, so nobody can open or mark someone else's.
     */
    public function show(Request $request, string $notification): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($notification);

        $notification->markAsRead();

        // The address was written by our own notification classes and is
        // always a path on this site, never an outside link.
        return redirect($notification->data['url']);
    }

    /**
     * Mark every notification as read.
     */
    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('success', 'All notifications are marked as read.');
    }
}
