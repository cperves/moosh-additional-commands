<?php

/**
 * moosh - Moodle Shell
 *
 * @copyright 2021 unistra {@link http://unistra.fr}
 * @author 2021 Céline Perves <cperves@unistra.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace Moosh\Command\Moodle39\Backup;
use Moosh\MooshCommand;

class BackupRemoveExcessBackup extends MooshCommand
{
    public function __construct() {
        parent::__construct('remove-excess-backup', 'backup');
        $this->addOption('a|all', 'all', false);
        $this->addOption('f|force', 'force : even courses not already programmed as backups', false);
        $this->addArgument('courseid');
        $this->minArguments = 0;
        $this->maxArguments = 1;
    }

    public function execute()
    {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/util/helper/backup_cron_helper.class.php');
        $options = $this->expandedOptions;
        $all = $options['all'];
        $force = $options['force'];
        $courseid = count($this->arguments)>0 ? $this->arguments[0] : 0;
        $now = time();

        if ($all) {
            $courses = null;
            if ($force) {
                cli_writeln('force mode, all courses backups will be cleaned up');
                $courses = get_all_courses();
            } else {
                // Only courses from backup_courses data table
                cli_writeln('normal mode, only courses programmed for backup will be cleaned up');
                $sql = 'SELECT c.*,
                       COALESCE(bc.nextstarttime, 1) nextstarttime
                  FROM {course} c
             LEFT JOIN {backup_courses} bc ON bc.courseid = c.id
                 WHERE bc.nextstarttime IS NULL OR bc.nextstarttime < :now
              ORDER BY nextstarttime ASC,
                       c.timemodified DESC';
                $params = array('now' => $now);
                $courses = $DB->get_recordset_sql($sql, $params);
            }
            $totalremovedcount = 0;
            foreach($courses as $course){
                $removedcount = \backup_cron_automated_helper::remove_excess_backups($course, time());
                $totalremovedcount += $removedcount;
                cli_writeln('remove '.($removedcount === false ? 0 : $removedcount).' backup files for course id='.$course->id );
            }
        } else if (!empty($courseid)) {
            $course = null;
            cli_writeln('course mode : only course with id='.$courseid.' will be treated');
            if($force){
                cli_writeln('force mode : course backup will be processed anyway');
                $course = $DB->get_record('course', array('id' => $courseid));
            } else {
                $sql = 'SELECT c.*,
                       COALESCE(bc.nextstarttime, 1) nextstarttime
                  FROM {course} c
             LEFT JOIN {backup_courses} bc ON bc.courseid = c.id
                 WHERE bc.nextstarttime IS NULL OR bc.nextstarttime < :now and c.id= :courseid
              ORDER BY nextstarttime ASC,
                       c.timemodified DESC';
                $params = array(
                    'now'=> $now,
                    'courseid' => $courseid
                    );
                $course = $DB->get_record_sql($sql, $params);
                if(!$course){
                    cli_error('course cleanup will not be processed since course is not programmed to be backup');
                }
            }
            $removedcount = \backup_cron_automated_helper::remove_excess_backups($course, time());
            cli_writeln('remove '.($removedcount === false ? 0 : $removedcount).' backup files for course id='.$course->id );
        } else {
            cli_error('must choose all mode (-a) or courseid (-c)');
        }
        cli_writeln ('end of process');
        return true;
    }
}