<?php

class shopPlugmeinPluginSettingsAction extends waViewAction
{
    private function getList()
    {
        $path = wa()->getAppPath('plugins', 'shop');
        $list=waFiles::listdir($path);
        foreach ($list as $key => $l) {
            if (!(is_dir($path.'/'.$l)&&file_exists($path.'/'.$l.'/lib/config/plugin.php'))) {
                unset($list[$key]);
            }
        }
        return ($list);
    }
    
    private function getOffInfo($id)
    {
        $app_config=wa()->getConfig()->getAppConfig('shop');
        $plugin_config=$app_config->getPluginPath($id)."/lib/config/plugin.php";
        if (!file_exists($plugin_config)) {
            return;
        }
        $plugin_info=include($plugin_config);
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
        $installer = ($this->getUser()->getRights('installer', 'settings')&&wa()->appExists('installer'));
        $plugin_info=array();
        $app_config=wa()->getConfig()->getAppConfig('shop');
        $path=$app_config->getConfigPath('plugins.php', true);
        $plugin_php=include($path);
        $plugin_all = $this->getList();
        $unlisted = array_diff_key(array_flip($plugin_all), $plugin_php);
        if (count($unlisted)>0) {
            $unlisted = array_combine(array_keys($unlisted), array_fill(0, count($unlisted), false));
            $plugin_php=array_merge($plugin_php, $unlisted);
        }
        $this->view->assign('app_config', $app_config->getPluginPath('plugmein'));
        $plugin_path=wa()->getAppStaticUrl('shop', true).'plugins/plugmein';
        $this->view->assign(compact('plugin_path', 'unlisted'));
        foreach ($plugin_php as $plugin['id'] => $plugin['active']) {
            if ($plugin['id']!='plugmein') {
                $plugin_info[$plugin['id']]=$this->getOffInfo($plugin['id']);
                $plugin_info[$plugin['id']]['id']=$plugin['id'];
                $plugin_info[$plugin['id']]['active']=$plugin['active'];
            }
        }
        unset($plugin);
        $this->view->assign('plugin_list', $plugin_info);
        $this->view->assign('installer', $installer);
    }
}
