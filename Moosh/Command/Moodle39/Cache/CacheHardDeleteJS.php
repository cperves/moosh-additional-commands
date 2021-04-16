<?php

/**
 * moosh - Moodle Shell
 *
 * @copyright  2012 onwards Tomasz Muras
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace Moosh\Command\Moodle39\Cache;
use Moosh\MooshCommand;

class CacheHardDeleteJS extends MooshCommand
{
    public function __construct() {
        parent::__construct('hard-delete-js', 'cache');
    }

    public function execute()
    {
        global $CFG;
        fulldelete("$CFG->localcachedir/requirejs");
        fulldelete("$CFG->localcachedir/js");
        return true;
    }
}