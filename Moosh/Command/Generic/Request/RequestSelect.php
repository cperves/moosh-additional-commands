<?php
/**
 * moosh - Moodle Shell
 *
 * @copyright  2012 onwards Tomasz Muras
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace Moosh\Command\Generic\Request;

use Moosh\MooshCommand;

class RequestSelect extends MooshCommand {
    public function __construct() {
        parent::__construct('select', 'request');
        $this->addArgument('select_query');
        $this->minArguments = 0;
        $this->maxArguments = 255;
    }

    protected function getArgumentsHelp() {
        $help = parent::getArgumentsHelp();
        $help .= "\n\n";
        $help .= "put a select request here";
        return $help;
    }


    public function execute() {
        global $CFG, $DB;
        $selectquery = trim($this->arguments[0]);
        $results = $DB->get_records_sql($selectquery);
        $output = '';
        foreach($results as $result) {
            $output.=explode(';',$result)."\t"."\n";
        }
        echo $output;
    }
}


