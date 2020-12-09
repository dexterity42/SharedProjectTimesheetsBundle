<?php


namespace KimaiPlugin\SharedProjectTimesheetsBundle\Model;


class MergeRecordMode
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
