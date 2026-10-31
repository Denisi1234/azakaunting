<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAuditLog extends Migration
{
    private function tableExists(string $table): bool
    {
        $row = $this->db->query('SELECT COUNT(*) AS cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?', [$this->db->database, $table])->getRow();

        return (int) $row->cnt > 0;
    }

    public function up(): void
    {
        $prefix = $this->db->getPrefix();

        if (! $this->tableExists($prefix . 'audit_log')) {
            $this->forge->addField([
                'audit_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'employee_id'  => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'action'       => ['type' => 'VARCHAR', 'constraint' => 64],
                'entity_type'  => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
                'entity_id'    => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
                'ip_address'   => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
                'before_data'  => ['type' => 'TEXT', 'null' => true],
                'after_data'   => ['type' => 'TEXT', 'null' => true],
                'created_at'   => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('audit_id', true);
            $this->forge->createTable('audit_log', true);
        }
    }

    public function down(): void
    {
        $prefix = $this->db->getPrefix();
        if ($this->tableExists($prefix . 'audit_log')) {
            $this->forge->dropTable('audit_log', true);
        }
    }
}
