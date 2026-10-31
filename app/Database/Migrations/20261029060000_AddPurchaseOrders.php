<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPurchaseOrders extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'purchase_order_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'order_number' => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true, 'default' => null],
            'supplier_id' => ['type' => 'INT', 'constraint' => 10, 'null' => true, 'default' => null],
            'location_id' => ['type' => 'INT', 'constraint' => 10],
            'created_by' => ['type' => 'INT', 'constraint' => 10],
            'approved_by' => ['type' => 'INT', 'constraint' => 10, 'null' => true, 'default' => null],
            'approval_reason' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null],
            'cancelled_by' => ['type' => 'INT', 'constraint' => 10, 'null' => true, 'default' => null],
            'cancelled_at' => ['type' => 'DATETIME', 'null' => true, 'default' => null],
            'cancellation_reason' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'draft'],
            'order_date' => ['type' => 'DATE'],
            'expected_date' => ['type' => 'DATE', 'null' => true, 'default' => null],
            'comment' => ['type' => 'TEXT', 'null' => true, 'default' => null],
            'created_at' => ['type' => 'DATETIME'],
            'approved_at' => ['type' => 'DATETIME', 'null' => true, 'default' => null],
            'completed_at' => ['type' => 'DATETIME', 'null' => true, 'default' => null],
        ]);
        $this->forge->addKey('purchase_order_id', true);
        $this->forge->addUniqueKey('order_number');
        $this->forge->addKey(['supplier_id', 'status', 'order_date']);
        $this->forge->addKey(['location_id', 'status', 'order_date']);
        $this->forge->addForeignKey('supplier_id', 'suppliers', 'person_id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('location_id', 'stock_locations', 'location_id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('created_by', 'employees', 'person_id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('approved_by', 'employees', 'person_id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('cancelled_by', 'employees', 'person_id', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('purchase_orders', true);

        $this->forge->addField([
            'purchase_order_item_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'purchase_order_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'item_id' => ['type' => 'INT', 'constraint' => 10],
            'ordered_quantity' => ['type' => 'DECIMAL', 'constraint' => '15,4'],
            'received_quantity' => ['type' => 'DECIMAL', 'constraint' => '15,4', 'default' => 0],
            'unit_cost' => ['type' => 'DECIMAL', 'constraint' => '15,4'],
        ]);
        $this->forge->addKey('purchase_order_item_id', true);
        $this->forge->addUniqueKey(['purchase_order_id', 'item_id']);
        $this->forge->addForeignKey('purchase_order_id', 'purchase_orders', 'purchase_order_id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('item_id', 'items', 'item_id', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('purchase_order_items', true);

        $this->forge->addColumn('receivings', [
            'purchase_order_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true, 'default' => null],
            'purchase_order_receipt_token' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true, 'default' => null],
            'purchase_order_discrepancy' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null],
        ]);
        $receivingsTable = $this->db->escapeIdentifiers($this->db->prefixTable('receivings'));
        $ordersTable = $this->db->escapeIdentifiers($this->db->prefixTable('purchase_orders'));
        $this->db->query('ALTER TABLE ' . $receivingsTable . ' ADD CONSTRAINT `receivings_purchase_order_fk` FOREIGN KEY (`purchase_order_id`) REFERENCES ' . $ordersTable . ' (`purchase_order_id`) ON DELETE SET NULL ON UPDATE CASCADE');
        $this->db->query('ALTER TABLE ' . $receivingsTable . ' ADD UNIQUE KEY `receivings_purchase_order_receipt_token_uq` (`purchase_order_id`, `purchase_order_receipt_token`)');
    }

    public function down(): void
    {
        $receivingsTable = $this->db->escapeIdentifiers($this->db->prefixTable('receivings'));
        $this->db->query('ALTER TABLE ' . $receivingsTable . ' DROP INDEX `receivings_purchase_order_receipt_token_uq`');
        $this->db->query('ALTER TABLE ' . $receivingsTable . ' DROP FOREIGN KEY `receivings_purchase_order_fk`');
        $this->forge->dropColumn('receivings', ['purchase_order_id', 'purchase_order_receipt_token', 'purchase_order_discrepancy']);
        $this->forge->dropTable('purchase_order_items', true);
        $this->forge->dropTable('purchase_orders', true);
    }
}
