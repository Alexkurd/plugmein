<?php
return array(
    'name' => /*_wp*/ 'PlugMein',
    'icon' => 'img/plugmein.png',
    'version' => '2.5.2',
    'vendor' => '991739',
    'description' => 'Plugin Manager',
    'shop_settings' => true,
    'custom_settings' => true,
    'handlers' =>
        array(
            'routing' => 'routingHook',
            'frontend_head' => 'headHook',
            'frontend_footer' => 'footerHook',
        ),
);