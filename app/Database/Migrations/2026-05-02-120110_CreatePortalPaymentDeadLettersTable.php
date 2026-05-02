<?php

declare(strict_types=1);

namespace App\Database\Migrations;

class CreatePortalPaymentDeadLettersTable extends AppMigration
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
            'job_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'employer_user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'reason' => [
                'type'       => 'VARCHAR',
                'constraint' => 80,
            ],
            'error_message' => ['type' => 'TEXT', 'null' => true],
            'payload'       => ['type' => 'TEXT', 'null' => true],
            'resolved_at'   => ['type' => 'DATETIME', 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('payment_intent_id');
        $this->forge->addKey('job_id');
        $this->forge->addKey('employer_user_id');
        $this->forge->addForeignKey('payment_intent_id', 'portal_payment_intents', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('job_id', 'portal_jobs', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('employer_user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('portal_payment_dead_letters');
    }

    public function down(): void
    {
        $this->forge->dropTable('portal_payment_dead_letters');
    }
}
