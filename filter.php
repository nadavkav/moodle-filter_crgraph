<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Display a block configurable reports - report graph.
 *
 * @package    filter
 * @subpackage crgraph
 * @copyright 2016 onwards Nadav kavalerchik
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// todo: add syntax instruction (or README)

class filter_crgraph extends moodle_text_filter {
    function filter($text, array $options = array()) {
        global $USER, $DB, $COURSE, $CFG;

        $CFG->cachetext = false; // very cpu intensive !!!

        // Do a quick check to avoid unnecessary work :  - Is there instance ? - Are we on the first page ?
        if (strpos($text, '[[crgraph(') === false) {
            return $text;
        }
        // There is job to do.... so let's do it !
        $pattern = '/\[\[crgraph\(([0-9]+)\)\]\]/';

        // If there is an instance again...
        while (preg_match($pattern, $text, $regs)) {

            // For each instance
            if ($regs[1] > 0) {
                $reportid = $regs[1];

//        if (!empty($alias)) {
//            if(! $report = $DB->get_record('block_configurable_reports',array('alias' => $alias)))
//                print_error('reportdoesnotexists','block_configurable_reports');
//        } else {
                if(!$report = $DB->get_record('block_configurable_reports',array('id' => $reportid)))
                    print_error('reportdoesnotexists','block_configurable_reports');
//        }

                require_once($CFG->dirroot.'/blocks/configurable_reports/locallib.php');
                require_once($CFG->dirroot.'/blocks/configurable_reports/report.class.php');
                require_once($CFG->dirroot.'/blocks/configurable_reports/reports/'.$report->type.'/report.class.php');

                $reportclassname = 'report_'.$report->type;
                $reportclass = new $reportclassname($report);
                $context = context_course::instance($COURSE->id);

                if (!$reportclass->check_permissions($USER->id, $context)){
                    print_error("badpermissions",'block_configurable_reports');
                }

                $reportclass->create_report();
                $graphimage = $reportclass->print_report_graph();

                $text = str_replace('[[crgraph('.$reportid.')]]', $graphimage, $text);
            } else {
                break;
            }
        }



        return $text;
    }
}

