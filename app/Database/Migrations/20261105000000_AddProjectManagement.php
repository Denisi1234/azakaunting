<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddProjectManagement extends Migration
{
    private function tableExists(string $table): bool
    {
        $realTable = $this->db->prefixTable($table);
        $row = $this->db->query('SELECT COUNT(*) AS cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?', [$this->db->database, $realTable])->getRow();

        return (int) ($row->cnt ?? 0) > 0;
    }

    private function columnExists(string $table, string $column): bool
    {
        $realTable = $this->db->prefixTable($table);
        $row = $this->db->query('SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?', [$this->db->database, $realTable, $column])->getRow();

        return (int) ($row->cnt ?? 0) > 0;
    }

    private function foreignKeyExists(string $table, string $column): bool
    {
        $realTable = $this->db->prefixTable($table);
        $row = $this->db->query('SELECT COUNT(*) AS cnt FROM information_schema.TABLE_CONSTRAINTS tc JOIN information_schema.KEY_COLUMN_USAGE kcu ON tc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME WHERE tc.TABLE_SCHEMA = ? AND kcu.TABLE_NAME = ? AND kcu.COLUMN_NAME = ? AND tc.CONSTRAINT_TYPE = "FOREIGN KEY"', [$this->db->database, $realTable, $column])->getRow();

        return (int) ($row->cnt ?? 0) > 0;
    }

    public function up(): void
    {
        // 1. Projects table
        if (! $this->tableExists('projects')) {
            $this->forge->addField([
                'project_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'project_number' => ['type' => 'VARCHAR', 'constraint' => 50],
                'project_name'   => ['type' => 'VARCHAR', 'constraint' => 255],
                'customer_id'    => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'employee_id'    => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'description'    => ['type' => 'TEXT', 'null' => true],
                'status'         => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active'],
                'start_date'     => ['type' => 'DATE', 'null' => true],
                'end_date'       => ['type' => 'DATE', 'null' => true],
                'budget_amount'  => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
                'deleted'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            ]);
            $this->forge->addKey('project_id', true);
            $this->forge->addUniqueKey('project_number');
            $this->forge->addForeignKey('customer_id', 'people', 'person_id', 'SET NULL', 'SET NULL');
            $this->forge->addForeignKey('employee_id', 'employees', 'person_id', 'SET NULL', 'SET NULL');
            $this->forge->createTable('projects', true);
        }

        // 2. project_id on sales
        if (! $this->columnExists('sales', 'project_id')) {
            $this->forge->addColumn('sales', [
                'project_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            ]);
        }
        if (! $this->foreignKeyExists('sales', 'project_id')) {
            $this->db->query('ALTER TABLE ' . $this->db->prefixTable('sales') . ' ADD CONSTRAINT fk_sales_project FOREIGN KEY (project_id) REFERENCES ' . $this->db->prefixTable('projects') . '(project_id) ON DELETE SET NULL');
        }

        // 3. project_id on expenses
        if (! $this->columnExists('expenses', 'project_id')) {
            $this->forge->addColumn('expenses', [
                'project_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            ]);
        }
        if (! $this->foreignKeyExists('expenses', 'project_id')) {
            $this->db->query('ALTER TABLE ' . $this->db->prefixTable('expenses') . ' ADD CONSTRAINT fk_expenses_project FOREIGN KEY (project_id) REFERENCES ' . $this->db->prefixTable('projects') . '(project_id) ON DELETE SET NULL');
        }

        // 4. Module, Permission, Grant
        $this->db->table('modules')->ignore(true)->insert([
            'name_lang_key' => 'Module.projects',
            'desc_lang_key' => 'Module.projects_desc',
            'sort'          => 87,
            'module_id'     => 'projects',
        ]);

        $this->db->table('permissions')->ignore(true)->insert([
            'permission_id' => 'projects',
            'module_id'     => 'projects',
        ]);

        $this->db->table('grants')->ignore(true)->insert([
            'person_id'     => 1,
            'permission_id' => 'projects',
            'menu_group'    => 'office',
        ]);
    }

    public function down(): void
    {
        if ($this->foreignKeyExists('sales', 'project_id')) {
            $this->db->query('ALTER TABLE ' . $this->db->prefixTable('sales') . ' DROP FOREIGN KEY fk_sales_project');
        }
        if ($this->columnExists('sales', 'project_id')) {
            $this->forge->dropColumn('sales', 'project_id');
        }

        if ($this->foreignKeyExists('expenses', 'project_id')) {
            $this->db->query('ALTER TABLE ' . $this->db->prefixTable('expenses') . ' DROP FOREIGN KEY fk_expenses_project');
        }
        if ($this->columnExists('expenses', 'project_id')) {
            $this->forge->dropColumn('expenses', 'project_id');
        }

        $this->forge->dropTable('projects', true);

        $this->db->table('grants')->where('permission_id', 'projects')->delete();
        $this->db->table('permissions')->where('permission_id', 'projects')->delete();
        $this->db->table('modules')->where('module_id', 'projects')->delete();
    }
}
