<?php

namespace App\Controllers;

use App\Models\Notification;
use CodeIgniter\HTTP\ResponseInterface;

class Notifications extends BaseController
{
    public function getIndex(): ResponseInterface
    {
        $personId      = (int) session()->get('person_id');
        $notifications = model(Notification::class)->get_for_person($personId)->getResult();

        return $this->response->setJSON(['notifications' => $notifications]);
    }

    public function getUnreadCount(): ResponseInterface
    {
        $personId = (int) session()->get('person_id');
        $count    = model(Notification::class)->get_unread_count($personId);

        return $this->response->setJSON(['count' => $count]);
    }

    public function postMarkRead(int $notificationId): ResponseInterface
    {
        $personId     = (int) session()->get('person_id');
        $notification = model(Notification::class);

        if ($notification->mark_read($notificationId, $personId)) {
            return $this->response->setJSON(['success' => true]);
        }

        return $this->response->setJSON(['success' => false]);
    }

    public function postMarkAllRead(): ResponseInterface
    {
        $personId     = (int) session()->get('person_id');
        $notification = model(Notification::class);

        if ($notification->mark_all_read($personId)) {
            return $this->response->setJSON(['success' => true]);
        }

        return $this->response->setJSON(['success' => false]);
    }
}
