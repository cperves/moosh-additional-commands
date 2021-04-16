<?php

/**
 * moosh - Moodle Shell
 * @copyright 2021 unistra {@link http://unistra.fr}
 * @author 2021 Céline Perves <cperves@unistra.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace Moosh\Command\Moodle39\Role;
use Moosh\MooshCommand;

class RoleUpdateCapabilityByCategoryId extends RoleUpdateCapability
{
    public function __construct()
    {
        parent::__construct();
        $this->name = "update-capability-by-category-id";
        $this->group = 'role';
        unset($this->options['contextid']);
        $this->addArgument('contextlevel');
    }

    public function execute()
    {
        global $DB;
        $arguments = $this->arguments;
        $context = $DB->get_record('context', array('contextlevel' => $arguments[4], 'instanceid' => $arguments[3]));
        if(!$context) {
            echo "Context with id '" . $arguments[3] . "and contextlevel " . $arguments[4] . "' does not exist\n";
            exit(0);
        }
        $this->arguments[3] = $context->id;
        unset($this->arguments[4]);
        parent::execute();
    }
}
