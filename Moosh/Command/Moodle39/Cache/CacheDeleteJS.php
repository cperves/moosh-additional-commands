<?php

/**
 * moosh - Moodle Shell
 *
 * 2021 unistra {@link http://unistra.fr}
 * @author 2021 Céline Perves <cperves@unistra.fr>
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