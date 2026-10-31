<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDiscountManagement extends Migration
{
    private function tableExists(string $table): bool
    {
        $row = $this->db->query('SELECT COUNT(*) AS cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?', [$this->db->database, $table])->getRow();

        return (int) $row->cnt > 0;
    }

    public function up(): void
    {
        $prefix = $this->db->getPrefix();

        if (! $this->tableExists($prefix . 'discounts')) {
            $this->forge->addField([
                'discount_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'discount_code'  => ['type' => 'VARCHAR', 'constraint' => 50],
                'discount_name'  => ['type' => 'VARCHAR', 'constraint' => 255],
                'discount_type'  => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'percentage'], // percentage or fixed
                'discount_value' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
                'min_purchase'   => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
                'max_uses'       => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'used_count'     => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
                'start_date'     => ['type' => 'DATE', 'null' => true],
                'end_date'       => ['type' => 'DATE', 'null' => true],
                'applies_to'     => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'all'], // all, items, categories
                'is_active'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'deleted'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            ]);
            $this->forge->addKey('discount_id', true);
            $this->forge->addUniqueKey('discount_code');
            $this->forge->createTable('discounts', true);
        }

        // Module, Permission, Grant
        $this->db->table('modules')->ignore(true)->insert([
            'name_lang_key' => 'Module.discounts',
            'desc_lang_key' => 'Module.discounts_desc',
            'sort'          => 89,
            'module_id'     => 'discounts',
        ]);
        $this->db->table('permissions')->ignore(true)->insert([
            'permission_id' => 'discounts',
            'module_id'     => 'discounts',
        ]);
        $this->db->table('grants')->ignore(true)->insert([
            'person_id'     => 1,
            'permission_id' => 'discounts',
            'menu_group'    => 'office',
        ]);
    }

    public function down(): void
    {
        $prefix = $this->db->getPrefix();
        if ($this->tableExists($prefix . 'discounts')) {
            $this->forge->dropTable('discounts', true);
        }
        $this->db->table('grants')->where('permission_id', 'discounts')->delete();
        $this->db->table('permissions')->where('permission_id', 'discounts')->delete();
        $this->db->table('modules')->where('module_id', 'discounts')->delete();
    }
}
