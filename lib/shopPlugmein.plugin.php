<?php

require_once __DIR__ . '/vendor/autoload.php';
use Tracy\Debugger;
use Netpromotion\Profiler\Profiler;

class shopPlugmeinPlugin extends shopPlugin
{

    static $templates;

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
        if ($this->installMysqliadapterHack() && $this->setMysqlidebugAdapter()) {
            $panel = new \Dzegarra\TracyMysqli\BarPanel();
            Debugger::getBar()->addPanel($panel);
        }
    }

    private function traceSmarty()
    {
        $panel = new shopPlugmeinPluginSmartyTrace();
        Debugger::getBar()->addPanel($panel);
        wa()->getView()->smarty->registerFilter('output', array('shopPlugmeinPlugin', 'smartyHelper'));
    }

    public static function smartyHelper($source, $template)
    {
        self::$templates[] = $template->source->name;
        return $source;
    }

    private function traceProfiler()
    {
        Profiler::enable();
        $panel = new Netpromotion\Profiler\Adapter\TracyBarAdapter();
        Debugger::getBar()->addPanel($panel);
    }

    private function traceSettings()
    {
        $panel = new shopPlugmeinPluginSettingsTrace();
        Debugger::getBar()->addPanel($panel);
    }

    /**
     * @param bool $init
     * @throws waException
     */
    private function traceEvent()
    {
        $panel = new shopPlugmeinPluginEventTrace();
        Debugger::getBar()->addPanel($panel);

        setcookie("event_log_execution", 1, 0, '/');
    }

    /**
     * @return bool|void
     */
    private function installMysqliadapterHack()
    {
        if (class_exists('waDbMysqlidebugAdapter')) {
            return true;
        }
        $config_path = wa()->getConfigPath() . '/SystemConfig.class.php';
        $config = file_get_contents($config_path);
        if ($config === false || $config === '') {
            return false;
        }
        if (false !== strpos($config, 'plugmein')) {
            // already patched
            return true;
        }
//        if (false !== strpos($config, 'function init')) {
//            // function already there, do not touch
//            return true;
//        }
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
        waFiles::write($config_path, $result);
    }

    private function setMysqlidebugAdapter()
    {
        $file = wa()->getConfigPath() . '/db.php';
        $db = @include $file;
        if ($db['default']['type'] == 'mysqlidebug') {
            return true;
        }
        if ($db['default']['type'] == 'mysqli') {
            $db['default']['type'] = 'mysqlidebug';
            waUtils::varExportToFile($db, $file);
        }
        return false;
    }

    private function uninstallHack()
    {

    }
}
