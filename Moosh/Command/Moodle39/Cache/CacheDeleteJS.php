<?php

/**
 * moosh - Moodle Shell
 *
 * @copyright  2012 onwards Tomasz Muras
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace Moosh\Command\Moodle39\Cache;
use Moosh\MooshCommand;

class CacheDeleteJS extends MooshCommand
{
    public function __construct() {
        parent::__construct('delete-js', 'cache');
    }

    public function execute()
    {
        global $CFG;
        require_once($CFG->libdir.'/outputrequirementslib.php');
        js_reset_all_caches();
        return true;
    }
}