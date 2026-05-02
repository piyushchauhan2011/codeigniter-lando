<?php

namespace App\Database\Migrations;


class CreatePortalJobsTable extends AppMigration
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
            'employer_user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'category_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 180,
            ],
            'description' => ['type' => 'TEXT'],
            'employment_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
            ],
            'location' => [
                'type'       => 'VARCHAR',
                'constraint' => 160,
            ],
            'salary_min' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'salary_max' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'draft',
            ],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('employer_user_id');
        $this->forge->addKey('category_id');
        $this->forge->addKey('status');
        $this->forge->addKey('location');
        $this->forge->addKey('employment_type');
        $this->forge->addForeignKey('employer_user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('category_id', 'job_categories', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('portal_jobs');
    }

    public function down(): void
    {
        $this->forge->dropTable('portal_jobs');
    }
}
