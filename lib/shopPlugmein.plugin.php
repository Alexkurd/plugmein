<?php

require_once __DIR__ . '/vendor/autoload.php';

use Netpromotion\Profiler\Profiler;
use Tracy\Debugger;

class shopPlugmeinPlugin extends shopPlugin
{

    static $templates;

    public static function smartyHelper($source, $template)
    {
        self::$templates[] = $template->source->name;
        return $source;
    }

    public function allHook($param, $name)
    {
        static $prev_name;

        if ($prev_name) {
            Profiler::finish($prev_name);
            $prev_name = null;
        }

        Profiler::start($name);
        $prev_name = $name;
    }

    public function routingHook()
    {
        static $init;

        if (!$init && wa()->getUser()->isAdmin() && $this->getSettings('debugbar')) {
            Debugger::enable(Debugger::DEVELOPMENT);
            Debugger::$maxDepth = 5;
            Debugger::$maxLength = 400;

            $this->traceMysql();
            $this->traceEvent();
            $this->traceProfiler();
            $this->traceSmarty();
            $this->traceSettings();

            $init = true;
        }
    }

    private function traceMysql()
    {
        if (class_exists('waDbMysqlidebugAdapter')) {
            $panel = new shopPlugmeinPluginMysqliTrace();
            Debugger::getBar()->addPanel($panel);
        }
    }

    private function traceEvent()
    {
//        $panel = new shopPlugmeinPluginEventTrace();
//        Debugger::getBar()->addPanel($panel);

        setcookie("event_log_execution", 1, 0, '/');
    }

    private function traceProfiler()
    {
        Profiler::enable();
        $panel = new shopPlugmeinPluginProfileTrace();
        Debugger::getBar()->addPanel($panel);
    }

    private function traceSmarty()
    {
        $panel = new shopPlugmeinPluginSmartyTrace();
        Debugger::getBar()->addPanel($panel);
        wa()->getView()->smarty->registerFilter('output', array('shopPlugmeinPlugin', 'smartyHelper'));
    }

    private function traceSettings()
    {
        $panel = new shopPlugmeinPluginSettingsTrace();
        Debugger::getBar()->addPanel($panel);
    }

    public function saveSettings($settings = array())
    {
        if (empty($settings['mysql'])) {
            self::uninstallHacks();
        } else {
            self::installHacks();
        }
        parent::saveSettings($settings);
    }

    public static function uninstallHacks()
    {
        $file = wa()->getConfigPath() . '/db.php';
        $db = @include $file;
        if ($db['default']['type'] == 'mysqlidebug') {
            $db['default']['type'] = 'mysqli';
            waUtils::varExportToFile($db, $file);
        }

        $config_path = wa()->getConfigPath() . '/SystemConfig.class.php';
        $config = file_get_contents($config_path);

        $remove = '/* plugmein v1 */
    public function init()
    {
        if (!waRequest::param("mysqlidebug")) {
            require __DIR__ . "/../wa-apps/shop/plugins/plugmein/lib/classes/waDbMysqlidebugAdapter.class.php";
            waRequest::setParam("mysqlidebug", 1);
        }
        parent::init();
    }
    /* end */';
        $result = str_replace($remove, '', $config);

        waFiles::write($config_path, $result);
    }

    public static function installHacks()
    {
        $config_path = wa()->getConfigPath() . '/SystemConfig.class.php';
        $config = file_get_contents($config_path);
        if ($config === false || $config === '') {
            return;
        }
        if (false !== strpos($config, 'plugmein')) {
            // already patched
        } elseif (false !== strpos($config, 'function init')) {
            // function already there, do not touch'
            waLog::log("Can't patch, init method already exists");
            return;
        } else {
            $replacement = '$1
    /* plugmein v1 */
    public function init()
    {
        if (!waRequest::param("mysqlidebug")) {
            require __DIR__ . "/../wa-apps/shop/plugins/plugmein/lib/classes/waDbMysqlidebugAdapter.class.php";
            waRequest::setParam("mysqlidebug", 1);
        }
        parent::init();
    }
    /* end */';
            $result = preg_replace('/(class SystemConfig extends waSystemConfig.*?{)/s', $replacement, $config);
            if (waFiles::write($config_path, $result)) {
                $file = wa()->getConfigPath() . '/db.php';
                $db = @include $file;
                if ($db['default']['type'] == 'mysqlidebug') {
                    return;
                }
                if ($db['default']['type'] == 'mysqli') {
                    $db['default']['type'] = 'mysqlidebug';
                    waUtils::varExportToFile($db, $file);
                }
            }
        }
    }
}
