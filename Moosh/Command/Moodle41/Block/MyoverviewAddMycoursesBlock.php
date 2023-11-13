<?php

/**
 * moosh - Moodle Shell
 * @copyright 2021 unistra {@link http://unistra.fr}
 * @author 2021 Céline Perves <cperves@unistra.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace Moosh\Command\Moodle41\Block;
use Moosh\MooshCommand;

class MyoverviewAddMycoursesBlock extends MooshCommand {
    public function __construct() {
        parent::__construct("add-mycourses-block", "myoverview");
    }
    public function execute(){
        global $DB, $CFG;
        require_once("{$CFG->dirroot}/my/lib.php");
        require_once("{$CFG->libdir}/db/upgradelib.php");
        // See if this block already somehow exists, it should not but who knows.
        $blockname = "myoverview";
        $pagetypepattern = "my-index";
        $subpagepattern = $DB->get_record("my_pages", [
            "userid" => null,
            "name" => MY_PAGE_COURSES,
            "private" => MY_PAGE_PUBLIC,
        ], "id", IGNORE_MULTIPLE)->id;

        $blockparams = [
            "blockname" => $blockname,
            "pagetypepattern" => $pagetypepattern,
            "subpagepattern" => $subpagepattern,
        ];
        if (!$DB->record_exists("block_instances", $blockparams)) {
            $page = new \moodle_page();
            $page->set_context(\context_system::instance());
            // Add the block to the default /my/courses.
            $page->blocks->add_region("content");
            $page->blocks->add_block($blockname, "content", 0, false, $pagetypepattern, $subpagepattern);
            cli_writeln("block added");
        } else {
            cli_writeln("block already exist");
        }
    }
}