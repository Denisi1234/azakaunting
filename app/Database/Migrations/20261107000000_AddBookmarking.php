<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddBookmarking extends Migration
{
    private function tableExists(string $table): bool
    {
        $row = $this->db->query('SELECT COUNT(*) AS cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?', [$this->db->database, $table])->getRow();

        return (int) $row->cnt > 0;
    }

    public function up(): void
    {
        $prefix = $this->db->getPrefix();

        if (! $this->tableExists($prefix . 'bookmarks')) {
            $this->forge->addField([
                'bookmark_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'person_id'   => ['type' => 'INT', 'constraint' => 11],
                'title'       => ['type' => 'VARCHAR', 'constraint' => 255],
                'url'         => ['type' => 'VARCHAR', 'constraint' => 500],
                'icon'        => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
                'sort_order'  => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
                'deleted'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            ]);
            $this->forge->addKey('bookmark_id', true);
            $this->forge->addForeignKey('person_id', 'people', 'person_id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('bookmarks', true);
        }
    }

    public function down(): void
    {
        $prefix = $this->db->getPrefix();
        if ($this->tableExists($prefix . 'bookmarks')) {
            $this->forge->dropTable($prefix . 'bookmarks', true);
        }
    }
}
