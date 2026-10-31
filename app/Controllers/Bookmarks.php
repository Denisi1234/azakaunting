<?php

namespace App\Controllers;

use App\Models\Bookmark;
use CodeIgniter\HTTP\ResponseInterface;

class Bookmarks extends BaseController
{
    public function getIndex(): ResponseInterface
    {
        $personId  = (int) session()->get('person_id');
        $bookmarks = model(Bookmark::class)->get_for_person($personId)->getResult();

        return $this->response->setJSON(['bookmarks' => $bookmarks]);
    }

    public function postAdd(): ResponseInterface
    {
        $personId = (int) session()->get('person_id');
        $title    = $this->request->getPost('title', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $url      = $this->request->getPost('url', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $icon     = $this->request->getPost('icon', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        if ($title === '' || $url === '') {
            return $this->response->setJSON(['success' => false, 'message' => lang('Bookmarks.required_fields')]);
        }

        $bookmark = model(Bookmark::class);

        if ($bookmark->add($personId, $title, $url, $icon ?: null)) {
            return $this->response->setJSON(['success' => true, 'message' => lang('Bookmarks.added')]);
        }

        return $this->response->setJSON(['success' => false, 'message' => lang('Bookmarks.error_adding')]);
    }

    public function postRemove(int $bookmarkId): ResponseInterface
    {
        $personId = (int) session()->get('person_id');
        $bookmark = model(Bookmark::class);

        if ($bookmark->remove($bookmarkId, $personId)) {
            return $this->response->setJSON(['success' => true, 'message' => lang('Bookmarks.removed')]);
        }

        return $this->response->setJSON(['success' => false, 'message' => lang('Bookmarks.error_removing')]);
    }
}
