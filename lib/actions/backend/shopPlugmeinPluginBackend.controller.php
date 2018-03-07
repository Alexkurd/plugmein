<?php

class shopPlugmeinPluginBackendController extends waJsonController
{
    public function execute()
    {
        if (!$this->getUser()->getRights('shop', 'settings')) {
            throw new waException(_w('Access denied'));
        }
        if (waRequest::get('id')=='savelist') {
            $this->saveList();
        }
        if (waRequest::post('id')=='run') {
            $this->generateConfig();
        }
    }
    
    private function saveList()
    {
        $app_config = wa()->getConfig()->getAppConfig('shop');
        $path=$app_config->getConfigPath('plugins.php', true);
        waFiles::readFile($path, "plugins.txt");
    }
    
    private function generateConfig()
    {
        
        $options = waRequest::post();
        $state = $options['state'];
        $plugins = $options['plugins'];
        
        $app_config = wa()->getConfig()->getAppConfig('shop');
        $path=$app_config->getConfigPath('plugins.php', true);
        $plugin_php = include $path;
        $state = ifset($state, false);

        foreach ($plugins as $plugin) {
            $plugin_php[$plugin]=$state;
        }
        unset($plugin);
        waUtils::varExportToFile($plugin_php, $path, true);
        if (wa()->appExists('installer')) {
            wa('installer');
            installerHelper::flushCache();
        }
    }
}
