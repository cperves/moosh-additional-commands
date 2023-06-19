<?php
/**
 * moosh - Moodle Shell
 *
 * @copyright  2012 onwards Tomasz Muras
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace Moosh\Command\Moodle39\Cohort;
use Moosh\MooshCommand;
use context_coursecat;

class CohortDelete extends MooshCommand
{
    public function __construct()
    {
        parent::__construct('delete', 'cohort');

        $this->addArgument('ids');
        $this->maxArguments = 255;
    }

    public function execute()
    {
        global $CFG, $DB;

        require_once $CFG->dirroot . '/cohort/lib.php';

        foreach ($this->arguments as $argument) {
            $ids = explode(',', $argument);
            foreach ($ids as $id) {
                $cohort = $DB->get_record('cohort',array('id'=>$id));
                if (!$cohort) {
                    echo "Cohort $id does not exists\n";
                    continue;
                }
                cohort_delete_cohort($cohort);
                cli_writeln("cohort $id deleted");
            }
        }
    }
}
