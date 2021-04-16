<?php

/**
 * moosh - Moodle Shell
 * @copyright 2021 unistra {@link http://unistra.fr}
 * @author 2021 Céline Perves <cperves@unistra.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace Moosh\Command\Moodle39\Role;
use Moosh\MooshCommand;

class RoleAllowAssign extends MooshCommand
{
    public function __construct() {
        parent::__construct('allow-assign', 'role');
        $this->addOption('d|disallow', 'disallow allow assignment');
        $this->addArgument('role');
        $this->addArgument('targetrole');
    }

    public function execute()
    {
        global $DB;
        // Don't create if already exists
        $role = $DB->get_record('role', array('shortname' => $this->arguments[0]));
        if (!$role) {
            echo 'Role '.$this->arguments[0].' not exists';
        }
        $targetrole = $DB->get_record('role', array('shortname' => $this->arguments[1]));
        if (!$targetrole) {
            echo 'Role '.$this->arguments[1].' not exists';
        }
        if($this->expandedOptions['disallow']){
            $DB->delete_records('role_allow_assign',array('roleid'=>$role->id,'allowassign'=>$targetrole->id));
            echo 'Assign role disallowed';
        }else{
            if(!$DB->get_record('role_allow_assign', array('roleid'=>$role->id, 'allowassign'=>$targetrole->id))){
                core_role_set_assign_allowed($role->id, $targetrole->id);
            }
            echo 'Assign role allowed';
        }
        echo "\n";
    }
}
