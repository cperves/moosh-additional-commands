<?php

/**
 * moosh - Moodle Shell
 *
 * @copyright  2012 onwards Tomasz Muras
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace Moosh\Command\Moodle39\Cache;
use Moosh\MooshCommand;

class CacheHardDeleteTheme extends MooshCommand
{
    public function __construct() {
        parent::__construct('hard-delete-theme', 'cache');
    }

    public function execute()
    {
        global $CFG;
        require_once($CFG->libdir.'/filelib.php');
        fulldelete("$CFG->localcachedir/theme");
        return true;
    }
}