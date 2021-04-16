<?php

/**
 * moosh - Moodle Shell
 *
 * @copyright  2012 onwards Tomasz Muras
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace Moosh\Command\Moodle39\Cache;
use Moosh\MooshCommand;

class CacheSet extends MooshCommand
{
    public function __construct() {
        parent::__construct('set', 'cache');
        $this->addArgument('definition');
        $this->addArgument('mapping');
    }

    public function execute()
    {
        global $CFG;
        require_once($CFG->dirroot.'/cache/locallib.php');
        $var_definition=$this->arguments[0];
        $var_mappings=$this->arguments[1];
        if(!isset($var_definition) || !isset($var_mappings) ){
            cli_error('Defintion and mampings are required');
        }
        $factory = \cache_factory::instance();
        list($component, $area) = explode('/', $var_definition, 2);
        $config = \cache_config::instance();
        $writer = \cache_config_writer::instance();
        $writer->update_definitions();
        $definition_check = $writer->get_definition_by_id($var_definition);
        if (!$definition_check) {
            cli_error("$var_definition cache definition not exists");
        }
        $definition = $factory->create_definition($component, $area);
        $possiblestores = $config->get_stores($definition->get_mode(), $definition->get_requirements_bin());

        $var_mappings = explode(',',$var_mappings);
        $mappings = array();
        foreach ($var_mappings as $index => $var_mapping){
            // Check mapping is available.
            if (array_key_exists($var_mapping, $possiblestores)){
                $mappings[$index]= $var_mapping;
            }else if(!empty($var_mapping)){
                cli_error("Bad store instance name mapping $var_mapping : does not exists or not usable for this cache mode");
            }
        }
        $writer->set_definition_mappings($var_definition, $mappings);
        return true;
    }
}