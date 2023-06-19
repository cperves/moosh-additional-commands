<?php
/**
 * moosh - Moodle Shell
 *
 * @copyright 2021 unistra {@link http://unistra.fr}
 * @author 2021 Céline Perves <cperves@unistra.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace Moosh\Command\Moodle39\Course;
use Moosh\MooshCommand;

class CourseVisibility extends MooshCommand
{
    public function __construct()
    {
        parent::__construct('visibility', 'course');
        $this->addArgument('courselist');
        $this->addArgument('visibility');
        $this->maxArguments = 255;
    }

    public function execute()
    {
        global $CFG;

        require_once $CFG->dirroot . '/course/lib.php';

        foreach ($this->arguments as $argument) {
            $this->expandOptionsManually(array($argument));
            $options = $this->expandedOptions;
            $course = new \stdClass();
            $course->fullname = $options['fullname'];
            $course->shortname = $argument;
            $course->description = $options['description'];
            $format = $options['format'];
            if(!$format){
            	$format = get_config('moodlecourse', 'format');
            }
            $course->format = $format;
            $numsections = $options['numsections'];
            if(!$numsections){
            	$numsections = get_config('moodlecourse', 'numsections');
            }
            $course->numsections = $numsections;
            $course->idnumber = $options['idnumber'];
            $visible = strtolower($options['visible']);
            if($visible == 'n' || $visible == 'no' ){
            	$visible = 0;
            }else{
            	$visible = 1;
            }
            $course->visible = $visible;
            $course->category = $options['category'];
            $course->summary = '';
            $course->summaryformat = FORMAT_HTML;
            $course->startdate = time();

            if ($options['reuse'] && $existing = $this->find_course($course)) {
                $newcourse = $existing;
            } else {
                //either use API create_course
                $newcourse = create_course($course);
            }

            echo $newcourse->id . "\n";
        }
    }

    public function find_course($course)
    {
        global $DB;
        $params = array('shortname' => $course->shortname);
        foreach (array('category', 'fullname', 'format', 'idnumber') as $param) {
            if ($course->$param) {
                $params[$param] = $course->$param;
            }
        }
        $courses = $DB->get_records('course', $params);
        if (count($courses) == 1) {
            return array_pop($courses);
        } else {
            return null;
        }
    }
}
