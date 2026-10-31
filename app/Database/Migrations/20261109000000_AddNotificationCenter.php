<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddNotificationCenter extends Migration
{
    private function tableExists(string $table): bool
    {
        $row = $this->db->query('SELECT COUNT(*) AS cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?', [$this->db->database, $table])->getRow();

        return (int) $row->cnt > 0;
    }

    public function up(): void
    {
        $prefix = $this->db->getPrefix();

        if (! $this->tableExists($prefix . 'notifications')) {
            $this->forge->addField([
                'notification_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'person_id'       => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'title'           => ['type' => 'VARCHAR', 'constraint' => 255],
                'message'         => ['type' => 'TEXT'],
                'type'            => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'info'], // info, warning, success, danger
                'link'            => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
                'is_read'         => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'created_at'      => ['type' => 'DATETIME', 'null' => true],
                'deleted'         => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            ]);
            $this->forge->addKey('notification_id', true);
            $this->forge->addForeignKey('person_id', 'people', 'person_id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('notifications', true);
        }
    }

    public function down(): void
    {
        $prefix = $this->db->getPrefix();
        if ($this->tableExists($prefix . 'notifications')) {
            $this->forge->dropTable('notifications', true);
        }
    }
}
