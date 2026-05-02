<?php

namespace App\Database\Migrations;


class CreateSavedJobsTable extends AppMigration
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
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'job_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['user_id', 'job_id']);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('job_id', 'portal_jobs', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('saved_jobs');
    }

    public function down(): void
    {
        $this->forge->dropTable('saved_jobs');
    }
}
