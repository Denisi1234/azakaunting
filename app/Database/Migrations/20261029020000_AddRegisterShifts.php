<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRegisterShifts extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'register_shift_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'employee_id' => ['type' => 'INT', 'constraint' => 10],
            'location_id' => ['type' => 'INT', 'constraint' => 10],
            'opened_at' => ['type' => 'DATETIME'],
            'opening_cash' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'closed_at' => ['type' => 'DATETIME', 'null' => true, 'default' => null],
            'closed_by' => ['type' => 'INT', 'constraint' => 10, 'null' => true, 'default' => null],
            'closing_cash' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => true, 'default' => null],
            'expected_cash' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => true, 'default' => null],
            'cash_variance' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => true, 'default' => null],
            'opening_note' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null],
            'closing_note' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null],
            'active_key' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true, 'default' => null],
        ]);
        $this->forge->addKey('register_shift_id', true);
        $this->forge->addKey(['employee_id', 'opened_at']);
        $this->forge->addKey(['location_id', 'opened_at']);
        $this->forge->addUniqueKey('active_key');
        $this->forge->addForeignKey('employee_id', 'employees', 'person_id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('closed_by', 'employees', 'person_id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('location_id', 'stock_locations', 'location_id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('register_shifts', true);

        $this->forge->addField([
            'movement_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'register_shift_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'employee_id' => ['type' => 'INT', 'constraint' => 10],
            'movement_type' => ['type' => 'VARCHAR', 'constraint' => 8],
            'amount' => ['type' => 'DECIMAL', 'constraint' => '15,2'],
            'reason' => ['type' => 'VARCHAR', 'constraint' => 255],
            'created_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('movement_id', true);
        $this->forge->addKey('register_shift_id');
        $this->forge->addForeignKey('register_shift_id', 'register_shifts', 'register_shift_id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('employee_id', 'employees', 'person_id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('register_shift_movements', true);

        $this->forge->addColumn('sales', [
            'register_shift_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true, 'default' => null, 'after' => 'employee_id'],
        ]);
        $this->db->query('ALTER TABLE ' . $this->db->prefixTable('sales') . ' ADD INDEX `sales_register_shift_id` (`register_shift_id`)');
        $this->db->query('ALTER TABLE ' . $this->db->prefixTable('sales') . ' ADD CONSTRAINT `sales_register_shift_fk` FOREIGN KEY (`register_shift_id`) REFERENCES ' . $this->db->prefixTable('register_shifts') . ' (`register_shift_id`) ON DELETE SET NULL ON UPDATE CASCADE');
    }

    public function down(): void
    {
        $this->db->query('ALTER TABLE ' . $this->db->prefixTable('sales') . ' DROP FOREIGN KEY `sales_register_shift_fk`');
        $this->db->query('ALTER TABLE ' . $this->db->prefixTable('sales') . ' DROP INDEX `sales_register_shift_id`');
        $this->forge->dropColumn('sales', 'register_shift_id');
        $this->forge->dropTable('register_shift_movements', true);
        $this->forge->dropTable('register_shifts', true);
    }
}
