<?php

declare(strict_types=1);

namespace KimaiPlugin\SharedProjectTimesheetsBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version2020120600000 extends AbstractMigration
{
    const SHARED_PROJECT_TIMESHEETS_TABLE_NAME = "kimai2_shared_project_timesheets";

    public function getDescription(): string
    {
        return "Create table for shared project timesheets";
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable(self::SHARED_PROJECT_TIMESHEETS_TABLE_NAME)) {
            $table = $schema->createTable(self::SHARED_PROJECT_TIMESHEETS_TABLE_NAME);
            $table->addColumn('id', Types::INTEGER, ['autoincrement' => true, 'notnull' => true]);
            $table->addColumn('project_id', Types::INTEGER, ['notnull' => true]);
            $table->addColumn('share_key', Types::STRING, ['length' => 20, 'notnull' => true]);
            $table->addColumn('password', Types::STRING, ['length' => 50, 'default' => null, 'notnull' => false]);
            $table->addColumn('entry_user_visible', Types::BOOLEAN, ['default' => false, 'notnull' => true]);
            $table->addColumn('entry_rate_visible', Types::BOOLEAN, ['default' => false, 'notnull' => true]);

            $table->setPrimaryKey(['id']);
            $table->addIndex(['share_key']);
            $table->addUniqueIndex(['project_id', 'share_key']);
            $table->addForeignKeyConstraint(
                'kimai2_projects',
                ['project_id'],
                ['id'],
                [
                    'onUpdate' => 'CASCADE',
                    'onDelete' => 'CASCADE',
                ]
            );
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable(self::SHARED_PROJECT_TIMESHEETS_TABLE_NAME)) {
            $schema->dropTable(self::SHARED_PROJECT_TIMESHEETS_TABLE_NAME);
        }
    }
}
