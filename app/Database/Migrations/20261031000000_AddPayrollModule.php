<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPayrollModule extends Migration
{
    public function up(): void
    {
        // 1. Employee Salary Structures (Base compensation profiles)
        $this->forge->addField([
            'employee_id'  => ['type' => 'INT', 'constraint' => 11],
            'pay_type'     => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'monthly'], // monthly, hourly
            'basic_salary' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'hourly_rate'  => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'allowances'   => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'deductions'   => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'notes'        => ['type' => 'TEXT', 'null' => true],
        ]);
        $this->forge->addKey('employee_id', true);
        $this->forge->addForeignKey('employee_id', 'employees', 'person_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('salary_structures', true);

        // 2. Payroll Runs (Batches per period)
        $this->forge->addField([
            'payroll_run_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'period_start'   => ['type' => 'DATE'],
            'period_end'     => ['type' => 'DATE'],
            'status'         => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'draft'], // draft, approved, paid
            'created_by'     => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'approved_at'    => ['type' => 'DATETIME', 'null' => true],
            'paid_at'        => ['type' => 'DATETIME', 'null' => true],
            'notes'          => ['type' => 'TEXT', 'null' => true],
        ]);
        $this->forge->addKey('payroll_run_id', true);
        $this->forge->createTable('payroll_runs', true);

        // 3. Payroll Items (Individual payslip line items per run)
        $this->forge->addField([
            'payroll_item_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'payroll_run_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'employee_id'     => ['type' => 'INT', 'constraint' => 11],
            'pay_type'        => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'monthly'],
            'total_hours'     => ['type' => 'DECIMAL', 'constraint' => '8,2', 'default' => 0],
            'basic_salary'    => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'allowances'      => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'deductions'      => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'net_pay'         => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'status'          => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'draft'],
            'paid_date'       => ['type' => 'DATE', 'null' => true],
            'notes'           => ['type' => 'TEXT', 'null' => true],
            'deleted'         => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
        ]);
        $this->forge->addKey('payroll_item_id', true);
        $this->forge->addForeignKey('payroll_run_id', 'payroll_runs', 'payroll_run_id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('employee_id', 'employees', 'person_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('payroll_items', true);

        // 4. Module, Permission, Grant (Proper order to satisfy foreign keys)
        $this->db->table('modules')->ignore(true)->insert([
            'name_lang_key' => 'Module.payroll',
            'desc_lang_key' => 'Module.payroll_desc',
            'sort'          => 86,
            'module_id'     => 'payroll',
        ]);

        $this->db->table('permissions')->ignore(true)->insert([
            'permission_id' => 'payroll',
            'module_id'     => 'payroll',
        ]);

        $this->db->table('grants')->ignore(true)->insert([
            'person_id'     => 1,
            'permission_id' => 'payroll',
            'menu_group'    => 'office',
        ]);
    }

    public function down(): void
    {
        $this->forge->dropTable('payroll_items', true);
        $this->forge->dropTable('payroll_runs', true);
        $this->forge->dropTable('salary_structures', true);
        $this->db->table('grants')->where('permission_id', 'payroll')->delete();
        $this->db->table('permissions')->where('permission_id', 'payroll')->delete();
        $this->db->table('modules')->where('module_id', 'payroll')->delete();
    }
}
