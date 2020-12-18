<?php
/**
 * This file is part of the SharedProjectTimesheetsBundle for Kimai 2.
 * All rights reserved by Fabian Vetter (https://vettersolutions.de).
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

declare(strict_types=1);

namespace KimaiPlugin\SharedProjectTimesheetsBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;
use KimaiPlugin\SharedProjectTimesheetsBundle\Model\RecordMergeMode;

final class Version2020120920000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Add record merge mode to shared project timesheets";
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable(Version2020120600000::SHARED_PROJECT_TIMESHEETS_TABLE_NAME)) {
            $table = $schema->getTable(Version2020120600000::SHARED_PROJECT_TIMESHEETS_TABLE_NAME);
            $table->addColumn(
                'record_merge_mode',
                Types::STRING,
                ['length' => 50, 'notnull' => true, 'default' => RecordMergeMode::MODE_NONE]
            );
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable(Version2020120600000::SHARED_PROJECT_TIMESHEETS_TABLE_NAME)) {
            $table = $schema->getTable(Version2020120600000::SHARED_PROJECT_TIMESHEETS_TABLE_NAME);
            $table->dropColumn('record_merge_mode');
        }
    }
}
