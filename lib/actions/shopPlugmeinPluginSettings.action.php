<?php

class shopPlugmeinPluginSettingsAction extends waViewAction
{
    private function getList()
    {
        $path = wa()->getAppPath('plugins', 'shop');
        $list = waFiles::listdir($path);
        foreach ($list as $key => $l) {
            if (!(is_dir($path.'/'.$l)&&file_exists($path.'/'.$l.'/lib/config/plugin.php'))) {
                unset($list[$key]);
            }
        }
        return ($list);
    }
    
    private function getOffInfo($id)
    {
        $app_config = wa()->getConfig()->getAppConfig('shop');
        $plugin_config = $app_config->getPluginPath($id)."/lib/config/plugin.php";
        if (!file_exists($plugin_config)) {
            return;
        }
        $plugin_info = include($plugin_config);
        foreach (array('name', 'description') as $field) {
            if (!empty($plugin_info[$field])) {
                $plugin_info[$field]=_wd('shop_'.$id, $plugin_info[$field]);
            }
        }
        return $plugin_info;
    }

    public function execute()
    {
        if (!$this->getUser()->getRights('shop', 'settings')) {
            throw new waException(_w('Access denied'));
        }
        $plugin_info = array();
        $app_config = wa()->getConfig()->getAppConfig('shop');
        $path = $app_config->getConfigPath('plugins.php', true);
        $plugin_php = include($path);
        $plugin_all = $this->getList();
        $unlisted = array_diff_key(array_flip($plugin_all), $plugin_php);
        if (count($unlisted)>0) {
            $unlisted = array_combine(array_keys($unlisted), array_fill(0, count($unlisted), false));
            $plugin_php = array_merge($plugin_php, $unlisted);
        }
        //Cache for 1 hour
        $md5 = md5(json_encode($plugin_all).filemtime($path));
        $cache = wa('shop')->getCache();
        if (!$cache || !($cache instanceof waCache)) {
            $cache = new waCache(new waFileCacheAdapter(array()), 'shop_plugmein');
        }
        $handlers_raw = $cache->get('handlers_'.$md5);
        $plugin_info = $cache->get('info_'.$md5);
        if (empty($plugin_info) || empty($handlers_raw)) {
            $handlers_raw = array();
            foreach ($plugin_php as $key => $state) {
                if ($key!='plugmein') {
                    $plugin_info[$key] = $this->getOffInfo($key);
                    $plugin_info[$key]['id'] = $key;
                    $plugin_info[$key]['active'] = $state;
                    if (!empty($plugin_info[$key]['handlers'])) {
                        $handlers[$key] = array_fill_keys(array_keys($plugin_info[$key]['handlers']), $key);
                        $handlers_raw = array_merge_recursive($handlers[$key], $handlers_raw);
                    }
                }
            }
            unset($plugin);
            ksort($handlers_raw);
            $cache->set('handlers_'.$md5, $handlers_raw, 3600);
            $cache->set('info_'.$md5, $plugin_info, 3600);
        }
        $this->view->assign('handlers', $handlers_raw);
        $this->view->assign('plugin_list', $plugin_info);
        //Check installer rights
        $installer = ($this->getUser()->getRights('installer', 'settings')&&wa()->appExists('installer'));
        $this->view->assign('installer', $installer);
    }
}
