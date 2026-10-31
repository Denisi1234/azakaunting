<?php

namespace App\Models;

use CodeIgniter\Database\ResultInterface;
use CodeIgniter\Model;

/**
 * Bookmark class
 */
class Bookmark extends Model
{
    protected $table            = 'bookmarks';
    protected $primaryKey       = 'bookmark_id';
    protected $useAutoIncrement = true;
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'person_id',
        'title',
        'url',
        'icon',
        'sort_order',
        'deleted',
    ];

    /**
     * Gets bookmarks for a person
     */
    public function get_for_person(int $person_id): ResultInterface
    {
        $builder = $this->db->table('bookmarks');
        $builder->where('person_id', $person_id);
        $builder->where('deleted', 0);
        $builder->orderBy('sort_order', 'asc');
        $builder->orderBy('title', 'asc');

        return $builder->get();
    }

    /**
     * Adds a bookmark
     */
    public function add(int $person_id, string $title, string $url, ?string $icon = null): bool
    {
        $builder = $this->db->table('bookmarks');

        return $builder->insert([
            'person_id'  => $person_id,
            'title'      => $title,
            'url'        => $url,
            'icon'       => $icon,
            'sort_order' => 0,
            'deleted'    => 0,
        ]);
    }

    /**
     * Removes a bookmark (soft delete)
     */
    public function remove(int $bookmark_id, int $person_id): bool
    {
        $builder = $this->db->table('bookmarks');
        $builder->where('bookmark_id', $bookmark_id);
        $builder->where('person_id', $person_id);

        return $builder->update(['deleted' => 1]);
    }
}
