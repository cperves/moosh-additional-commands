<?php


namespace Moosh\Command\Moodle39\Role;

use Moosh\MooshCommand;

class RoleGetId extends MooshCommand
{

    public function __construct()
    {
        parent::__construct('get-id', 'role');
        $this->addArgument('shortname');

    }

    public function execute() {
        global $DB;
        $role = $DB->get_record('role', array('shortname' => $this->arguments[0]));
        if(!$role) {
            cli_error("role wih shortname $shortname does not exists");
        }
        cli_writeln($role->id);
    }

}