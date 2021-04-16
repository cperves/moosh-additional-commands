<?php
/**
 * moosh - Moodle Shell command
 *
 * @author Céline Pervès cperves@unistra.fr
 * @copyright Université de Strasbourg unistra.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace Moosh\Command\Moodle35\My;


use Moosh\MooshCommand;

class ResetMyMoodle extends MooshCommand {
    public function __construct() {
        parent::__construct('reset','mymoodle');
        $this->addArgument('userid');
    }

    public function execute(){
        global $CFG;
        require_once($CFG->dirroot.'/my/lib.php');
        $userid = $this->arguments[0];
        $return = my_reset_page($userid);
        if(!$return){
            exit(0);
        }
        echo "page reset for user with id $userid";
    }
}