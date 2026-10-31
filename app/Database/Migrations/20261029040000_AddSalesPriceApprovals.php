<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSalesPriceApprovals extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'approval_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'requester_id' => ['type' => 'INT', 'constraint' => 10],
            'approver_id' => ['type' => 'INT', 'constraint' => 10, 'null' => true, 'default' => null],
            'location_id' => ['type' => 'INT', 'constraint' => 10],
            'sale_id' => ['type' => 'INT', 'constraint' => 10, 'null' => true, 'default' => null],
            'session_key' => ['type' => 'VARCHAR', 'constraint' => 64],
            'line_key' => ['type' => 'VARCHAR', 'constraint' => 64],
            'item_id' => ['type' => 'INT', 'constraint' => 10],
            'item_name' => ['type' => 'VARCHAR', 'constraint' => 255],
            'old_price' => ['type' => 'DECIMAL', 'constraint' => '15,4'],
            'requested_price' => ['type' => 'DECIMAL', 'constraint' => '15,4'],
            'old_total' => ['type' => 'DECIMAL', 'constraint' => '15,4', 'null' => true, 'default' => null],
            'requested_total' => ['type' => 'DECIMAL', 'constraint' => '15,4', 'null' => true, 'default' => null],
            'old_discount' => ['type' => 'DECIMAL', 'constraint' => '15,4'],
            'requested_discount' => ['type' => 'DECIMAL', 'constraint' => '15,4'],
            'old_discount_type' => ['type' => 'TINYINT', 'constraint' => 1],
            'requested_discount_type' => ['type' => 'TINYINT', 'constraint' => 1],
            'reason' => ['type' => 'VARCHAR', 'constraint' => 255],
            'status' => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'pending'],
            'decision_reason' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null],
            'created_at' => ['type' => 'DATETIME'],
            'decided_at' => ['type' => 'DATETIME', 'null' => true, 'default' => null],
            'applied_at' => ['type' => 'DATETIME', 'null' => true, 'default' => null],
        ]);
        $this->forge->addKey('approval_id', true);
        $this->forge->addKey(['status', 'location_id', 'created_at']);
        $this->forge->addKey(['requester_id', 'session_key', 'status']);
        $this->forge->addKey('sale_id');
        $this->forge->addForeignKey('requester_id', 'employees', 'person_id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('approver_id', 'employees', 'person_id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('location_id', 'stock_locations', 'location_id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('sale_id', 'sales', 'sale_id', 'RESTRICT', 'SET NULL');
        $this->forge->createTable('sales_price_approvals', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('sales_price_approvals', true);
    }
}
