<?php
/**
 * moosh - Moodle Shell
 *
 * @copyright  2012 onwards Tomasz Muras
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Kacper Golewski <k.golewski@gmail.com>
 */

namespace Moosh\Command\Generic\Settings;
use Moosh\MooshCommand;

class SettingsDefault extends MooshCommand
{
    public function __construct()
    {
        parent::__construct('default', 'settings');
    }

    public function execute()
    {
        global $CFG;
        require_once($CFG->libdir.'/adminlib.php');
        $admin = get_admin();//need to be admin in order to retrieve all admin tree
        \core\session\manager::init_empty_session();

        \core\session\manager::set_user($admin);
        $adminroot = admin_get_root(); // need all settings
        $newsettings = self::admin_new_settings_by_page($adminroot);
        foreach($newsettings as $plugin => $newsettingnode){
            foreach($newsettingnode as $key => $value){
                try {
                    set_config($key, $value == null ? '' : $value, $plugin);
                    echo "plugin {$plugin} key {$key} value {$value} set!";
                }catch(Exception $e){
                    echo "Error while setting config for plugin = $plugin key = $key value = $value";
                    echo "Correct trouble and relaunch cmdlinetool to apply default setting for this plugin";
                }
            }
        }
        return true;
    }

    private static function admin_new_settings_by_page($node) {
        $return = array();
        if ($node instanceof admin_category) {
            $entries = array_keys($node->children);
            foreach ($entries as $entry) {
                $return += apply_defaults_settings_to_all_plugins_cli::admin_new_settings_by_page($node->children[$entry]);
            }

        } else if ($node instanceof admin_settingpage) {
            $newsettings = array();
            foreach ($node->settings as $setting) {
                if (is_null($setting->get_setting())) {
                    $newsettings[] = $setting;
                }
            }
            if (count($newsettings) > 0) {
                $adminroot = admin_get_root();
                $newsettingsobj = array();
                foreach ($newsettings as $setting){
                    if (is_null($setting->get_setting())) {
                        if(!array_key_exists($setting->plugin, $return)){
                            $return[$setting->plugin] = array();
                        }
                        $return[$setting->plugin][$setting->name]=$setting->get_defaultsetting();
                    }

                }
            }

        }
        return $return;
    }
}