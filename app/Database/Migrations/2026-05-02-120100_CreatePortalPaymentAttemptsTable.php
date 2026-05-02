<?php

declare(strict_types=1);

namespace App\Database\Migrations;

class CreatePortalPaymentAttemptsTable extends AppMigration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'payment_intent_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'attempt_number' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
            ],
            'scenario' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
            ],
            'provider_reference' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => true,
            ],
            'error_message' => ['type' => 'TEXT', 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('payment_intent_id');
        $this->forge->addKey(['payment_intent_id', 'attempt_number']);
        $this->forge->addForeignKey('payment_intent_id', 'portal_payment_intents', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('portal_payment_attempts');
    }

    public function down(): void
    {
        $this->forge->dropTable('portal_payment_attempts');
    }
}
