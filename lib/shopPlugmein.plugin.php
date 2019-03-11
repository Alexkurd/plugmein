<?php

require_once __DIR__ . '/vendor/autoload.php';
use Tracy\Debugger;

class shopPlugmeinPlugin extends shopPlugin
{
    public static $eventTiming;

    public function routingHook()
    {
        static $init;

        if (!$init && wa()->getUser()->isAdmin()) {
            Debugger::enable(Debugger::DEVELOPMENT);
            Debugger::$maxDepth = 5;
            Debugger::$maxLength = 400;

            if ($this->installMysqliadapterHack() && $this->setMysqlidebugAdapter()) {
                $this->traceMysql();
            }
            $this->traceEvent();
            $this->traceSmarty();

            $init = true;
        }
    }

    private function traceMysql()
    {
        $panel = new \Dzegarra\TracyMysqli\BarPanel();
        Debugger::getBar()->addPanel($panel);
    }


    public function traceSmarty()
    {
        $panel = new shopPlugmeinPluginSmartyTrace();
        Debugger::getBar()->addPanel($panel);
    }

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
