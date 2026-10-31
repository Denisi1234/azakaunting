<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddQuotationManagement extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'sale_id'           => ['type' => 'INT', 'constraint' => 10],
            'status'            => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'draft'],
            'reference_number'  => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'valid_until'       => ['type' => 'DATE'],
            'template_code'     => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'modern'],
            'public_token'      => ['type' => 'CHAR', 'constraint' => 64],
            'revision_number'   => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'default' => 1],
            'internal_notes'    => ['type' => 'TEXT', 'null' => true],
            'customer_response' => ['type' => 'TEXT', 'null' => true],
            'created_by'        => ['type' => 'INT', 'constraint' => 10, 'null' => true],
            'sent_at'           => ['type' => 'DATETIME', 'null' => true],
            'viewed_at'         => ['type' => 'DATETIME', 'null' => true],
            'accepted_at'       => ['type' => 'DATETIME', 'null' => true],
            'rejected_at'       => ['type' => 'DATETIME', 'null' => true],
            'converted_at'      => ['type' => 'DATETIME', 'null' => true],
            'converted_sale_id' => ['type' => 'INT', 'constraint' => 10, 'null' => true],
            'cancelled_at'      => ['type' => 'DATETIME', 'null' => true],
            'created_at'        => ['type' => 'DATETIME'],
            'updated_at'        => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('sale_id', true);
        $this->forge->addUniqueKey('public_token');
        $this->forge->addKey(['status', 'valid_until']);
        $this->forge->addForeignKey('sale_id', 'sales', 'sale_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('quotations', true);

        $this->forge->addField([
            'quotation_revision_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'sale_id'               => ['type' => 'INT', 'constraint' => 10],
            'revision_number'       => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'snapshot'              => ['type' => 'LONGTEXT'],
            'revision_note'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_by'            => ['type' => 'INT', 'constraint' => 10, 'null' => true],
            'created_at'            => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('quotation_revision_id', true);
        $this->forge->addUniqueKey(['sale_id', 'revision_number']);
        $this->forge->addForeignKey('sale_id', 'sales', 'sale_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('quotation_revisions', true);

        $this->forge->addField([
            'quotation_option_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'sale_id'             => ['type' => 'INT', 'constraint' => 10],
            'description'         => ['type' => 'VARCHAR', 'constraint' => 255],
            'quantity'            => ['type' => 'DECIMAL', 'constraint' => '15,3', 'default' => 1],
            'unit_price'          => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'notes'               => ['type' => 'TEXT', 'null' => true],
            'sort_order'          => ['type' => 'INT', 'constraint' => 10, 'default' => 0],
        ]);
        $this->forge->addKey('quotation_option_id', true);
        $this->forge->addKey('sale_id');
        $this->forge->addForeignKey('sale_id', 'sales', 'sale_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('quotation_options', true);

        $this->forge->addField([
            'quotation_attachment_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'sale_id'                 => ['type' => 'INT', 'constraint' => 10],
            'file_name'               => ['type' => 'VARCHAR', 'constraint' => 255],
            'stored_name'             => ['type' => 'VARCHAR', 'constraint' => 80],
            'mime_type'               => ['type' => 'VARCHAR', 'constraint' => 100],
            'file_size'               => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'uploaded_by'             => ['type' => 'INT', 'constraint' => 10, 'null' => true],
            'uploaded_at'             => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('quotation_attachment_id', true);
        $this->forge->addKey('sale_id');
        $this->forge->addForeignKey('sale_id', 'sales', 'sale_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('quotation_attachments', true);

        $this->db->table('modules')->ignore(true)->insert([
            'name_lang_key' => 'Module.quotations',
            'desc_lang_key' => 'Module.quotations_desc',
            'sort'          => 87,
            'module_id'     => 'quotations',
        ]);
        $this->db->table('permissions')->ignore(true)->insert([
            'permission_id' => 'quotations',
            'module_id'     => 'quotations',
        ]);
        $this->db->table('grants')->ignore(true)->insert([
            'person_id'     => 1,
            'permission_id' => 'quotations',
            'menu_group'    => 'office',
        ]);

        $existingQuotes = $this->db->table('sales')->where('sale_type', SALE_TYPE_QUOTE)->get()->getResult();

        foreach ($existingQuotes as $quote) {
            $this->db->table('quotations')->insert([
                'sale_id'         => $quote->sale_id,
                'status'          => 'draft',
                'valid_until'     => date('Y-m-d', strtotime($quote->sale_time . ' +30 days')),
                'public_token'    => bin2hex(random_bytes(32)),
                'revision_number' => 1,
                'created_by'      => $quote->employee_id,
                'created_at'      => $quote->sale_time,
                'updated_at'      => $quote->sale_time,
            ]);
        }
    }

    public function down(): void
    {
        $this->db->table('grants')->where('permission_id', 'quotations')->delete();
        $this->db->table('permissions')->where('permission_id', 'quotations')->delete();
        $this->db->table('modules')->where('module_id', 'quotations')->delete();
        $this->forge->dropTable('quotation_attachments', true);
        $this->forge->dropTable('quotation_options', true);
        $this->forge->dropTable('quotation_revisions', true);
        $this->forge->dropTable('quotations', true);
    }
}
