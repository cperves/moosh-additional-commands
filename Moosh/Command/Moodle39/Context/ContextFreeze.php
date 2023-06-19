<?php
namespace Moosh\Command\Moodle39\Context;

use Moosh\MooshCommand;
use context_course;
use course_enrolment_manager;

class ContextFreeze extends MooshCommand {
    public function __construct() {
        parent::__construct('freeze', 'context');
        $this->addArgument('instanceid');
        $this->addArgument('contextlevel');
        $this->addArgument('lock');
    }
    public function execute(){
        global $CFG;
        require_once("{$CFG->libdir}/accesslib.php");
        require_once("{$CFG->libdir}/adminlib.php");
        $instanceid = $this->arguments[0];
        $contextlevel = $this->arguments[1];
        $lock = $this->arguments[2];
        $context = null;
        switch($contextlevel){
            case CONTEXT_SYSTEM :
                $context = \context_system::instance();
                break;
            case CONTEXT_COURSE :
                $context = \context_course::instance($instanceid);
                break;
            case CONTEXT_COURSECAT :
                $context = \context_coursecat::instance($instanceid);
                break;
            case CONTEXT_MODULE :
                $context = \context_module::instance($instanceid);
                break;
            case CONTEXT_BLOCK :
                $context = \context_block::instance($instanceid);
                break;
            case CONTEXT_USER :
                $context = \context_user::instance($instanceid);
                break;
            default:
                cli_error("bad context level $contextlevel");
        }
        if($context){
            $context->set_locked($lock);
            if($lock){
                cli_writeln("context locked");
            } else {
                cli_writeln("context unlocked");
            }
        } else {
            cli_error("context not found");
        }
    }
}