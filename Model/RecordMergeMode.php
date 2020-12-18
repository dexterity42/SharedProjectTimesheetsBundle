<?php
/**
 * This file is part of the SharedProjectTimesheetsBundle for Kimai 2.
 * All rights reserved by Fabian Vetter (https://vettersolutions.de).
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace KimaiPlugin\SharedProjectTimesheetsBundle\Model;


class RecordMergeMode
{
    const MODE_NONE = 'NONE';
    const MODE_MERGE = 'MERGE';
    const MODE_MERGE_USE_FIRST_OF_DAY = 'MERGE_USE_FIRST_OF_DAY';
    const MODE_MERGE_USE_LAST_OF_DAY = 'MERGE_USE_LAST_OF_DAY';

    public static function getModes() {
        return [
            self::MODE_NONE => 'shared_project_timesheets.model.merge_record_mode.none',
            self::MODE_MERGE => 'shared_project_timesheets.model.merge_record_mode.merge',
            self::MODE_MERGE_USE_FIRST_OF_DAY => 'shared_project_timesheets.model.merge_record_mode.merge_use_first_of_day',
            self::MODE_MERGE_USE_LAST_OF_DAY => 'shared_project_timesheets.model.merge_record_mode.merge_use_last_of_day',
        ];
    }

}
