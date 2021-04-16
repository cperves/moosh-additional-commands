<?php

/**
 * moosh - Moodle Shell
 * @copyright 2021 unistra {@link http://unistra.fr}
 * @author 2021 Céline Perves <cperves@unistra.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace Moosh\Command\Moodle39\LocalAssignmentCapas;
use Moosh\MooshCommand;

class LocalAssignmentCapasCapabilityManage extends MooshCommand
{
    public function __construct() {
        parent::__construct('capability-manage', 'local-assigment-capas');
        $this->addArgument('mode');
    }

    public function execute()
    {
        global $DB;
        switch($this->arguments[0]){
            case 'add' :
                list($capaslist, $index) = self::candidates_capabilities(false);
                $promptmsg = "Choose the plugin for which a new add instance capability while be created :";
                $var_index = self::int_values_check_and_prompt(range(1,$index),$promptmsg);
                $capability = new \stdClass();
                $capability->name         = $capaslist[$var_index];
                $capability->captype      = 'read';
                $capability->contextlevel = CONTEXT_MODULE;
                $capability->component='moosh'; // Unexisting plugin component to prevent cache deletion while updating access.php plugin
                $DB->insert_record('capabilities', $capability, false);
                \cache::make('core', 'capabilities')->delete('core_capabilities');
                cli_writeln('capability '.$capaslist[$var_index].' created');
                break;
            case 'remove' :
                list($capaslist, $index) = self::candidates_capabilities(true);
                $promptmsg = "choose the plugin for which a new add instance capability while be removed :";
                $var_index = self::int_values_check_and_prompt(range(1,$index),$promptmsg);
                $errors = 0;
                if ($roles = get_roles_with_capability($capaslist[$var_index])) {
                    foreach($roles as $role) {
                        if (!unassign_capability($capaslist[$var_index], $role->id)) {
                            $errors++;
                            cli_writeln("cannot unassign capability on role $role->shortname");
                        }
                    }
                }
                if($errors == 0){
                    $DB->delete_records('capabilities', array('name'=>$capaslist[$var_index]));
                }else{
                    cli_error('capability '.$capaslist[$var_index].' not removed');
                }
                \cache::make('core', 'capabilities')->delete('core_capabilities');
                cli_writeln('capability '.$capaslist[$var_index].' removed');
                break;
            default :
                cli_error('bad option '.$this->arguments[0]);
                break;
        }
    }
    function int_values_check_and_prompt($possiblevalues,$promptmsg){
        $var = trim(cli_input($promptmsg));
        if(!is_numeric($var)){
            cli_writeln("Entered value must be an int");
            return self::int_values_check_and_prompt($possiblevalues,$promptmsg);
        }
        if(!in_array($var, $possiblevalues)){
            cli_writeln("entered value must be an int");
            return self::int_values_check_and_prompt($possiblevalues,$promptmsg);
        }
        return $var;
    }

    /**
     * @return array
     */
    private static function candidates_capabilities($existing=true) : array {
        //list of assignment and feedback plugins
        $capaslist = array();
        $plugins = \core_component::get_plugin_list('assignfeedback');
        $index = 0;
        foreach ($plugins as $plugin => $path) {
            if (get_capability_info('local/assignment_capas:assign_feedback_' . $plugin . '_addinstance') == $existing) {
                $index++;
                $capaslist[$index] = 'local/assignment_capas:assign_feedback_' . $plugin . '_addinstance';
                echo "$index : $plugin (feedback)\n";
            }
        }
        $plugins = \core_component::get_plugin_list('assignsubmission');
        foreach ($plugins as $plugin => $path) {
            if (get_capability_info('local/assignment_capas:assign_submission_' . $plugin . '_addinstance') == $existing) {
                $index++;
                $capaslist[$index] = 'local/assignment_capas:assign_submission_' . $plugin . '_addinstance';
                echo "$index : $plugin (submission)\n";
            }
        }

        if ($index == 0) {
            cli_error("No assignment plugin");
            die;
        }
        return array($capaslist, $index);
    }
}