<?php

declare(strict_types=1);

namespace App\Database\Migrations;

class AddFeaturedFieldsToPortalJobsTable extends AppMigration
{
    public function up(): void
    {
        $this->forge->addColumn('portal_jobs', [
            'is_featured' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'unsigned'   => true,
                'default'    => 0,
                'after'      => 'status',
            ],
            'featured_until' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'is_featured',
            ],
        ]);

        $this->forge->addKey('is_featured');
        $this->forge->processIndexes('portal_jobs');
    }

    public function down(): void
    {
        $this->forge->dropColumn('portal_jobs', ['is_featured', 'featured_until']);
    }
}
