<?php
/**
 * moosh - Moodle Shell Command
 *
 * @copyright 2021 Université de Strasbourg {@link http://unistra.fr}
 * @author 2022 Céline Perves <cperves@unistra.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace Moosh\Command\Moodle39\Module;
use Moosh\MooshCommand;

class ModuleDataImport extends MooshCommand
{
    public function __construct()
    {
        parent::__construct('data-import', 'module');
        $this->addOption('e|encoding:', 'file encoding', 'UTF-8');
        $this->addOption('d|delimiter:', 'file delimiter', ',');
        $this->addArgument('dataid');
        $this->addArgument('filepath');

    }

    public function execute() {
        global $DB;
        $encoding = $this->expandedOptions['encoding'];
        $fielddelimiter = $this->expandedOptions['delimiter'];
        $dataid = (int)$this->arguments[0];
        $data   = $DB->get_record('data', array('id'=>$dataid), '*', MUST_EXIST);
        $course = $DB->get_record('course', array('id'=>$data->course), '*', MUST_EXIST);
        $cm     = get_coursemodule_from_instance('data', $data->id, $course->id, false, MUST_EXIST);

        $file = $this->arguments[1];
        if (!file_exists($file[0])) {
            cli_error("File '" . $file . "' does not exist.");
        }

        if (!is_readable($file[0])) {
            cli_error("File '" . $file . "' is not readable.");
        }
        $filecontent = file_get_contents($file);
        if(!$filecontent) {
            cli_error("Can't open file $file");
        }
        self::data_import_csv($course, $cm, $data, $filecontent, $encoding,$fielddelimiter);
    }

    private static function data_import_csv($course, $cm, $data, &$csvdata, $encoding, $fielddelimiter) {
        global $CFG, $DB;
        require_once($CFG->libdir.'/classes/php_time_limit.php');
        require_once($CFG->libdir.'/csvlib.class.php');
        require_once($CFG->libdir.'/accesslib.php');
        require_once($CFG->libdir.'/completionlib.php');
        require_once($CFG->dirroot.'/tag/classes/tag.php');
        require_once($CFG->dirroot.'/mod/data/lib.php');
        // Large files are likely to take their time and memory. Let PHP know
        // that we'll take longer, and that the process should be recycled soon
        // to free up memory.
        \core_php_time_limit::raise();
        raise_memory_limit(MEMORY_EXTRA);

        $iid = \csv_import_reader::get_new_iid('moddata');
        $cir = new \csv_import_reader($iid, 'moddata');

        $context = \context_module::instance($cm->id);

        $readcount = $cir->load_csv_content($csvdata, $encoding, $fielddelimiter);
        $csvdata = null; // Free memory.
        if (empty($readcount)) {
            cli_error('csv failed');
        } else {
            if (!$fieldnames = $cir->get_columns()) {
                cli_error('cannot read csv file');
            }

            // Check the fieldnames are valid.
            $rawfields = $DB->get_records('data_fields', array('dataid' => $data->id), '', 'name, id, type');
            $fields = array();
            $errorfield = '';
            $usernamestring = get_string('username');
            $safetoskipfields = array(get_string('user'), get_string('email'),
                get_string('timeadded', 'data'), get_string('timemodified', 'data'),
                get_string('approved', 'data'), get_string('tags', 'data'), 'groupid');
            $userfieldid = null;
            foreach ($fieldnames as $id => $name) {
                if (!isset($rawfields[$name])) {
                    if ($name == $usernamestring) {
                        $userfieldid = $id;
                    } else if (!in_array($name, $safetoskipfields)) {
                        $errorfield .= "'$name' ";
                    }
                } else {
                    // If this is the second time, a field with this name comes up, it must be a field not provided by the user...
                    // like the username.
                    if (isset($fields[$name])) {
                        if ($name == $usernamestring) {
                            $userfieldid = $id;
                        }
                        unset($fieldnames[$id]); // To ensure the user provided content fields remain in the array once flipped.
                    } else {
                        $field = $rawfields[$name];
                        require_once("$CFG->dirroot/mod/data/field/$field->type/field.class.php");
                        $classname = 'data_field_' . $field->type;
                        $fields[$name] = new $classname($field, $data, $cm);
                    }
                }
            }

            if (!empty($errorfield)) {
                cli_error("field not matched $errorfield");
            }

            $fieldnames = array_flip($fieldnames);

            $cir->init();
            $recordsadded = 0;
            $count = 0;
            while ($record = $cir->next()) {
                $count++;
                $authorid = null;
                if ($userfieldid) {
                    if (!($author = core_user::get_user_by_username($record[$userfieldid], 'id'))) {
                        $authorid = null;
                    } else {
                        $authorid = $author->id;
                    }
                }
                $groupid = 0;
                if (isset($fieldnames['groupid'])){
                    $columnindex = $fieldnames['groupid'];
                    $groupid = $record[$columnindex];
                    if (!empty($groupid)) {
                        if(!$DB->get_record('groups', array('id'=>$groupid, 'courseid' => $course->id), '*')) {
                            cli_problem("group with id $groupid does not exists in course $course->id, line $count is skipped");
                            continue;
                        }
                    } else {
                        $groupid = 0;
                    }
                }
                if ($recordid = data_add_record($data, $groupid, $authorid)) {  // Add instance to data_record.
                    foreach ($fields as $field) {
                        $fieldid = $fieldnames[$field->field->name];
                        if (isset($record[$fieldid])) {
                            $value = $record[$fieldid];
                        } else {
                            $value = '';
                        }

                        if (method_exists($field, 'update_content_import')) {
                            $field->update_content_import($recordid, $value, 'field_' . $field->field->id);
                        } else {
                            $content = new \stdClass();
                            $content->fieldid = $field->field->id;
                            $content->content = $value;
                            $content->recordid = $recordid;
                            $DB->insert_record('data_content', $content);
                        }
                    }

                    if (\core_tag_tag::is_enabled('mod_data', 'data_records') &&
                        isset($fieldnames[get_string('tags', 'data')])) {
                        $columnindex = $fieldnames[get_string('tags', 'data')];
                        $rawtags = $record[$columnindex];
                        $tags = explode(',', $rawtags);
                        foreach ($tags as $tag) {
                            $tag = trim($tag);
                            if (empty($tag)) {
                                continue;
                            }
                            \core_tag_tag::add_item_tag('mod_data', 'data_records', $recordid, $context, $tag);
                        }
                    }

                    $recordsadded++;
                    cli_writeln("record added $recordsadded with id $recordid");
                }
            }
            $cir->close();
            $cir->cleanup(true);
            return $recordsadded;
        }
        return 0;
    }

}
