<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/vendor/VarExportParser.php';
use Tracy\Debugger;

class shopPlugmeinPlugin extends shopPlugin
{
    public static $eventTiming;

    public function routingHook()
    {
        static $init;

        if (!$init && wa()->getUser()->isAdmin()) {
            Debugger::enable(Debugger::DEVELOPMENT);
            Debugger::$maxDepth = 5; // default: 3
            Debugger::$maxLength = 400; // default: 150
            $this->traceMysql();
            $this->traceEvent();
//            $this->traceSmarty();

            wa()->getView()->smarty->registerFilter('output', array('shopPlugmeinPlugin', 'traceSmarty'));

            $init = true;
        }
    }

    public function headHook()
    {
        trigger_error("Какой-то ворнинг из PHP", E_USER_WARNING);
        return '';
    }

    public function footerHook()
    {
        $this->traceEvent(false);
    }

    private function traceMysql()
    {
        $panel = new \Dzegarra\TracyMysqli\BarPanel();
        Debugger::getBar()->addPanel($panel);
    }

    /**
     * @param $source
     * @param Smarty_Internal_Template $template
     * @return mixed
     */
    public static function traceSmarty($source, $template)
    {
        if ($template->template_resource === 'file:index.html') {
            shopPlugmeinPluginSmartyTrace::$ptr = Smarty_Internal_Debug::get_debug_vars($template);
            $panel = new shopPlugmeinPluginSmartyTrace();
            Debugger::getBar()->addPanel($panel);
        }

        return $source;
    }

    /**
     * @param bool $init
     * @throws waException
     */
    private function traceEvent($init = true)
    {
        if ($init) {
            $panel = new shopPlugmeinPluginEventTrace();
            Debugger::getBar()->addPanel($panel);

            setcookie("event_log_execution", 1, 0, '/');
        } else {
            $log = file_get_contents(waConfig::get('wa_path_log') . '/webasyst/waEventExecutionTime.log');
            $re = '/Recorded.*?(array.*?)===/ms';
            preg_match_all($re, $log, $matches);
            foreach ($matches[1] as $match) {
                self::$eventTiming[] = eval('return ' . $match . ';');
            }
            waFiles::delete(waConfig::get('wa_path_log') . '/webasyst/waEventExecutionTime.log');
        }
    }

    /**
     * @return bool|void
     */
    private function installConfigHack()
    {
        $config_path = wa()->getConfigPath() . '/SystemConfig.class.php';
        $config = file_get_contents($config_path);
        if ($config === false || $config === '') {
            return false;
        }
        if (false !== strpos($config, 'plugmein')) {
            // already patched
            return;
        }
        if (false !== strpos($config, 'function init')) {
            // function already there, do not touch, use hook
            return;
        }
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

    private function uninstallHack()
    {

    }
}