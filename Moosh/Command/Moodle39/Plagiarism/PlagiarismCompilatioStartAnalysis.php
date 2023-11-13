<?php

/**
 * moosh - Moodle Shell
 * @copyright 2021 unistra {@link http://unistra.fr}
 * @author 2021 Céline Perves <cperves@unistra.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace Moosh\Command\Moodle39\Plagiarism;
use Moosh\MooshCommand;

class PlagiarismCompilatioStartAnalysis extends MooshCommand
{
    public $statuscodestrings = array(
                        "202" => "Accepted",
                        "203" =>"Analysing",
                        "415"=>"Unsupported",
                        "416"=>"Unextractable",
                        "412" =>"Too short",
                        "413" =>"Too large",
                        "414" =>"Too long",
                        "404" =>"Not found",
                        "418"=>"Failed analysis"
                        );

public function __construct()
    {
        parent::__construct('compilatio-start-analysis', 'plagiarism');

        $this->addOption('c|cmid:', 'cmid');
        $this->addArgument('id');
        $this->maxArguments = 255;
    }

    public function execute(){
        global $CFG, $DB;
        require_once($CFG->dirroot . '/plagiarism/lib.php');
        require_once($CFG->dirroot . '/plagiarism/compilatio/compilatio.class.php');
        require_once($CFG->dirroot . '/plagiarism/compilatio/lib.php');
        require_once($CFG->dirroot . '/plagiarism/compilatio/constants.php');
        $id = $this->arguments[0];
        $options = $this->expandedOptions;
        $cmmod = $options['cmid'];
        if($cmmod) {
            cli_writeln("cmid $id case");
            $plugincm = compilatio_cm_use($id);
            if (!$plugincm) {
                cli_error("Compilatio activity with cmid $id doesntt exists.");
            }
            $countsuccess = 0;
            $plagiarismfiles = $docsfailed = $docsinextraction = [];
            $sql = "cm = ? AND statuscode = ?";
            $params = array($id, COMPILATIO_STATUSCODE_ACCEPTED);
            $plagiarismfiles = $DB->get_records_select('plagiarism_compilatio_files', $sql, $params);

            foreach ($plagiarismfiles as $file) {

                if (compilatio_student_analysis($plugincm['compi_student_analyses'], $id, $file->userid)) {
                    continue;
                }
                $res = compilatio_startanalyse($file);
                if ($res === true) {
                    $countsuccess++;
                } else if ($res == get_string('extraction_in_progress', 'plagiarism_compilatio')) {
                    $docsinextraction[] = $file->filename;
                } else {
                    $docsfailed[] = $file->filename;
                }
            }
            // Handle not sent documents :.
            $files = compilatio_get_non_uploaded_documents($id);
            $countbegin = count($files);

            if ($countbegin != 0) {
                define("COMPILATIO_MANUAL_SEND", true);
                compilatio_upload_files($files, $id);
                $countsuccess += $countbegin - count(compilatio_get_non_uploaded_documents($id));
            }
            $counttotal = count($plagiarismfiles) + $countbegin;
            cli_writeln("Compilatio analysis for cmid $id succeed");
            cli_writeln("$countsuccess/$counttotal file have been successfully analysed");
            cli_writeln (count($docsinextraction).'files in extaction');
            cli_writeln (count($docsfailed).'files failed');
        } else {
            cli_writeln("docid $id case");
            $plagiarismfile = $DB->get_record('plagiarism_compilatio_files', array('id' => $id));
            if(!$plagiarismfile) {
                cli_error("Compilatio document with id $id doesntt exists.");
            }
            if($plagiarismfile->statuscode == 'pending' ) {
                cli_error("doc compilatio in pending state : wating for upload on compilatio server.");
            }
            cli_writeln("current status is $plagiarismfile->statuscode".(is_numeric($plagiarismfile->statuscode)? " : ".$this->statuscodestrings[$plagiarismfile->statuscode]:""));
            $res = compilatio_startanalyse($plagiarismfile);
            if ($res === true) {
                cli_writeln("Compilatio doc $id succesfully analysed");
            } else {
                cli_writeln($res);
            }
        }

    }

}