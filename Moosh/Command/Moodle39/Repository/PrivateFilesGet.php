<?php
/**
* moosh - Moodle Shell
* @copyright 2021 unistra {@link http://unistra.fr}
* @author 2021 Céline Perves <cperves@unistra.fr>
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

namespace Moosh\Command\Moodle39\Repository;

use Moosh\MooshCommand;

class PrivateFilesGet extends MooshCommand{
    public function __construct() {
        parent::__construct('privatefiles-get','repository');
        $this->addOption('d|delete:', 'delete', false);
        $this->addOption('s|suffix:','suffixe');
        $this->addOption('r|directory:','directory');
        $this->addArgument('username');
    }

    public function execute() {
        global $CFG, $DB;
        $fs = get_file_storage();
        $deleteafter = $this->expandedOptions['delete'];
        $username = $this->arguments[0];
        $user = $DB->get_record('user', array('username' => $username));
        if(!$user){
            cli_error("user with name $username does not exist");
        }
        $sql = "SELECT  f.id,contenthash,filepath,filename FROM mdl_files f inner join mdl_context ctx on ctx.id=f.contextid inner join mdl_user u on u.id=ctx.instanceid and ctx.contextlevel=:usercontextlevel LEFT JOIN mdl_files_reference r ON f.referencefileid = r.id WHERE u.username=:username AND f.component = 'user' AND f.filearea = 'private' AND f.itemid =0";
        $privatefiles = $DB->get_records_sql($sql,array('usercontextlevel'=>CONTEXT_USER, 'username'=>$user->username));
        if(!$privatefiles){
            cli_writeln("No private files found.");
            die(1);
        }
        $userctx = \context_user::instance($user->id);
        $itemid=0;
        $destfilepath = $CFG->tempdir.DIRECTORY_SEPARATOR.'userprivatefiles/';
        if(!file_exists($destfilepath)){
            mkdir($destfilepath);
        }
        $directory = empty($this->expandedOptions['directory'])?'/':$this->expandedOptions['directory'];
        $filename = 'userprivatefiles_'.$user->username.(empty($this->expandedOptions['suffix'])?'':'_'.$this->expandedOptions['suffix']).'.zip';
        $zipper = get_file_packer('application/zip');
        $storedfile = $fs->get_file($userctx->id, 'user', 'private', $itemid, $directory, '.');

        if (!empty($storedfile) && $zipper->archive_to_pathname(array('/' => $storedfile), $destfilepath.$filename)) {
            $file = $destfilepath.$filename;
            cli_writeln("$username Private files extracted in $file. Don't forget to deleted them after retrieve");
            if($deleteafter){
                $files = $fs->get_directory_files($userctx->id, 'user', 'private', $itemid, $directory, true);
                foreach ($files as $file) {
                    $file->delete();
                }
                //delete directory if not root
                if($directory!=DIRECTORY_SEPARATOR) {
                    $directoryfile = $fs->get_file($userctx->id, 'user', 'private', $itemid, $directory, '.');
                    if($directoryfile) {
                        $directoryfile->delete();
                    }
                }
                cli_writeln("Files sucessfully deleted for user");
            }
        } else {
            cli_error("Problems while arciving private files");
        }
    }

}
