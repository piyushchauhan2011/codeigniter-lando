<?php

declare(strict_types=1);

namespace App\Database\Migrations;

class CreatePortalPaymentIntentsTable extends AppMigration
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
            'idempotency_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
            ],
            'provider_reference' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => true,
            ],
            'amount_cents' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'currency' => [
                'type'       => 'VARCHAR',
                'constraint' => 3,
                'default'    => 'USD',
            ],
            'scenario' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
                'default'    => 'success',
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'pending',
            ],
            'attempts_count' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'default'    => 0,
            ],
            'last_error'       => ['type' => 'TEXT', 'null' => true],
            'paid_at'          => ['type' => 'DATETIME', 'null' => true],
            'dead_lettered_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('job_id');
        $this->forge->addKey('employer_user_id');
        $this->forge->addKey('status');
        $this->forge->addUniqueKey('idempotency_key');
        $this->forge->addForeignKey('job_id', 'portal_jobs', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('employer_user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('portal_payment_intents');
    }

    public function down(): void
    {
        $this->forge->dropTable('portal_payment_intents');
    }
}
