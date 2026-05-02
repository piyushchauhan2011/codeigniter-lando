<?php

namespace App\Database\Migrations;


class CreateJobApplicationsTable extends AppMigration
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
            'seeker_user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'cover_letter' => ['type' => 'TEXT'],
            'resume_path' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'submitted',
            ],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['job_id', 'seeker_user_id']);
        $this->forge->addForeignKey('job_id', 'portal_jobs', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('seeker_user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('job_applications');
    }

    public function down(): void
    {
        $this->forge->dropTable('job_applications');
    }
}
