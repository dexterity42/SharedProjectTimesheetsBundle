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

final class Version2020122010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Add flags to enable charts of shared project timesheets";
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable(Version2020120600000::SHARED_PROJECT_TIMESHEETS_TABLE_NAME)) {
            $table = $schema->getTable(Version2020120600000::SHARED_PROJECT_TIMESHEETS_TABLE_NAME);

            $table->addColumn('annual_chart_visible', Types::BOOLEAN, ['default' => false, 'notnull' => true]);
            $table->addColumn('monthly_chart_visible', Types::BOOLEAN, ['default' => false, 'notnull' => true]);
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable(Version2020120600000::SHARED_PROJECT_TIMESHEETS_TABLE_NAME)) {
            $table = $schema->getTable(Version2020120600000::SHARED_PROJECT_TIMESHEETS_TABLE_NAME);

            $table->dropColumn('annual_chart_visible');
            $table->dropColumn('monthly_chart_visible');
        }
    }

}
