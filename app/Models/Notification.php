<?php

namespace App\Models;

use CodeIgniter\Database\ResultInterface;
use CodeIgniter\Model;

/**
 * Notification class
 */
class Notification extends Model
{
    protected $table            = 'notifications';
    protected $primaryKey       = 'notification_id';
    protected $useAutoIncrement = true;
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'person_id',
        'title',
        'message',
        'type',
        'link',
        'is_read',
        'created_at',
        'deleted',
    ];

    public function get_for_person(int $person_id, int $limit = 20): ResultInterface
    {
        $builder = $this->db->table('notifications');
        $builder->where('deleted', 0);
        $builder->groupStart();
        $builder->where('person_id', $person_id);
        $builder->orWhere('person_id', null);
        $builder->groupEnd();
        $builder->orderBy('created_at', 'desc');
        $builder->limit($limit);

        return $builder->get();
    }

    public function get_unread_count(int $person_id): int
    {
        $builder = $this->db->table('notifications');
        $builder->where('deleted', 0);
        $builder->where('is_read', 0);
        $builder->groupStart();
        $builder->where('person_id', $person_id);
        $builder->orWhere('person_id', null);
        $builder->groupEnd();

        return $builder->countAllResults();
    }

    public function mark_read(int $notification_id, int $person_id): bool
    {
        $builder = $this->db->table('notifications');
        $builder->where('notification_id', $notification_id);
        $builder->groupStart();
        $builder->where('person_id', $person_id);
        $builder->orWhere('person_id', null);
        $builder->groupEnd();

        return $builder->update(['is_read' => 1]);
    }

    public function mark_all_read(int $person_id): bool
    {
        $builder = $this->db->table('notifications');
        $builder->where('is_read', 0);
        $builder->where('deleted', 0);
        $builder->groupStart();
        $builder->where('person_id', $person_id);
        $builder->orWhere('person_id', null);
        $builder->groupEnd();

        return $builder->update(['is_read' => 1]);
    }

    public function create(int $person_id, string $title, string $message, string $type = 'info', ?string $link = null): bool
    {
        $builder = $this->db->table('notifications');

        return $builder->insert([
            'person_id'  => $person_id > 0 ? $person_id : null,
            'title'      => $title,
            'message'    => $message,
            'type'       => $type,
            'link'       => $link,
            'is_read'    => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'deleted'    => 0,
        ]);
    }
}
