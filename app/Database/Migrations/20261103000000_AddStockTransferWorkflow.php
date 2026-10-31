<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddStockTransferWorkflow extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'transfer_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'transfer_number' => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true, 'default' => null],
            'source_location_id' => ['type' => 'INT', 'constraint' => 10],
            'destination_location_id' => ['type' => 'INT', 'constraint' => 10],
            'created_by' => ['type' => 'INT', 'constraint' => 10],
            'approved_by' => ['type' => 'INT', 'constraint' => 10, 'null' => true, 'default' => null],
            'dispatched_by' => ['type' => 'INT', 'constraint' => 10, 'null' => true, 'default' => null],
            'received_by' => ['type' => 'INT', 'constraint' => 10, 'null' => true, 'default' => null],
            'cancelled_by' => ['type' => 'INT', 'constraint' => 10, 'null' => true, 'default' => null],
            'status' => ['type' => 'VARCHAR', 'constraint' => 24],
            'comment' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null],
            'discrepancy_note' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null],
            'cancellation_reason' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null],
            'created_at' => ['type' => 'DATETIME'],
            'approved_at' => ['type' => 'DATETIME', 'null' => true, 'default' => null],
            'dispatched_at' => ['type' => 'DATETIME', 'null' => true, 'default' => null],
            'received_at' => ['type' => 'DATETIME', 'null' => true, 'default' => null],
            'cancelled_at' => ['type' => 'DATETIME', 'null' => true, 'default' => null],
        ]);
        $this->forge->addKey('transfer_id', true);
        $this->forge->addUniqueKey('transfer_number');
        $this->forge->addKey(['source_location_id', 'status']);
        $this->forge->addKey(['destination_location_id', 'status']);
        $this->forge->addForeignKey('source_location_id', 'stock_locations', 'location_id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('destination_location_id', 'stock_locations', 'location_id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('created_by', 'employees', 'person_id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('approved_by', 'employees', 'person_id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('dispatched_by', 'employees', 'person_id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('received_by', 'employees', 'person_id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('cancelled_by', 'employees', 'person_id', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('stock_transfers', true);

        $this->forge->addField([
            'transfer_item_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'transfer_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'item_id' => ['type' => 'INT', 'constraint' => 10],
            'requested_quantity' => ['type' => 'DECIMAL', 'constraint' => '15,4'],
            'dispatched_quantity' => ['type' => 'DECIMAL', 'constraint' => '15,4', 'default' => 0],
            'received_quantity' => ['type' => 'DECIMAL', 'constraint' => '15,4', 'default' => 0],
        ]);
        $this->forge->addKey('transfer_item_id', true);
        $this->forge->addUniqueKey(['transfer_id', 'item_id']);
        $this->forge->addForeignKey('transfer_id', 'stock_transfers', 'transfer_id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('item_id', 'items', 'item_id', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('stock_transfer_items', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('stock_transfer_items', true);
        $this->forge->dropTable('stock_transfers', true);
    }
}
