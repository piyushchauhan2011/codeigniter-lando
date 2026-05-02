<?php

declare(strict_types=1);

namespace App\Database\Migrations;

class CreateJobAssetsTable extends AppMigration
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
            'bucket' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
            ],
            'object_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 512,
            ],
            'original_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'mime_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
            ],
            'size_bytes' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'visibility' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'private',
            ],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['job_id', 'employer_user_id']);
        $this->forge->addUniqueKey('object_key');
        $this->forge->addForeignKey('job_id', 'portal_jobs', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('employer_user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('job_assets');
    }

    public function down(): void
    {
        $this->forge->dropTable('job_assets');
    }
}
