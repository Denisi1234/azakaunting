<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMultiCurrency extends Migration
{
    private function tableExists(string $table): bool
    {
        $row = $this->db->query('SELECT COUNT(*) AS cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?', [$this->db->database, $table])->getRow();

        return (int) $row->cnt > 0;
    }

    private function columnExists(string $table, string $column): bool
    {
        $row = $this->db->query('SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?', [$this->db->database, $table, $column])->getRow();

        return (int) $row->cnt > 0;
    }

    public function up(): void
    {
        $prefix = $this->db->getPrefix();

        // 1. Currencies table
        if (! $this->tableExists($prefix . 'currencies')) {
            $this->forge->addField([
                'currency_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'currency_code'   => ['type' => 'VARCHAR', 'constraint' => 3],
                'currency_name'   => ['type' => 'VARCHAR', 'constraint' => 100],
                'currency_symbol' => ['type' => 'VARCHAR', 'constraint' => 10],
                'exchange_rate'   => ['type' => 'DECIMAL', 'constraint' => '15,6', 'default' => 1],
                'is_default'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'deleted'         => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            ]);
            $this->forge->addKey('currency_id', true);
            $this->forge->addUniqueKey('currency_code');
            $this->forge->createTable('currencies', true);

            // Seed default currencies
            $this->db->table('currencies')->insertBatch([
                ['currency_code' => 'USD', 'currency_name' => 'US Dollar', 'currency_symbol' => '$', 'exchange_rate' => 1, 'is_default' => 0, 'deleted' => 0],
                ['currency_code' => 'EUR', 'currency_name' => 'Euro', 'currency_symbol' => '€', 'exchange_rate' => 0.92, 'is_default' => 0, 'deleted' => 0],
                ['currency_code' => 'GBP', 'currency_name' => 'British Pound', 'currency_symbol' => '£', 'exchange_rate' => 0.79, 'is_default' => 0, 'deleted' => 0],
                ['currency_code' => 'TZS', 'currency_name' => 'Tanzanian Shilling', 'currency_symbol' => 'TSh', 'exchange_rate' => 2500, 'is_default' => 1, 'deleted' => 0],
            ]);
        }

        // 2. currency_id on sales
        if (! $this->columnExists($prefix . 'sales', 'currency_id')) {
            $this->forge->addColumn('sales', [
                'currency_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            ]);
        }

        // 3. currency_id on expenses
        if (! $this->columnExists($prefix . 'expenses', 'currency_id')) {
            $this->forge->addColumn('expenses', [
                'currency_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            ]);
        }

        // 4. Module, Permission, Grant
        $this->db->table('modules')->ignore(true)->insert([
            'name_lang_key' => 'Module.currencies',
            'desc_lang_key' => 'Module.currencies_desc',
            'sort'          => 88,
            'module_id'     => 'currencies',
        ]);
        $this->db->table('permissions')->ignore(true)->insert([
            'permission_id' => 'currencies',
            'module_id'     => 'currencies',
        ]);
        $this->db->table('grants')->ignore(true)->insert([
            'person_id'     => 1,
            'permission_id' => 'currencies',
            'menu_group'    => 'office',
        ]);
    }

    public function down(): void
    {
        $prefix = $this->db->getPrefix();

        if ($this->columnExists($prefix . 'sales', 'currency_id')) {
            $this->forge->dropColumn('sales', 'currency_id');
        }
        if ($this->columnExists($prefix . 'expenses', 'currency_id')) {
            $this->forge->dropColumn('expenses', 'currency_id');
        }
        if ($this->tableExists($prefix . 'currencies')) {
            $this->forge->dropTable('currencies', true);
        }

        $this->db->table('grants')->where('permission_id', 'currencies')->delete();
        $this->db->table('permissions')->where('permission_id', 'currencies')->delete();
        $this->db->table('modules')->where('module_id', 'currencies')->delete();
    }
}
