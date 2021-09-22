<?php

return array(
    'name' => /*_wp*/'PlugMein',
    'icon' => 'img/plugmein.png',
    'version' => '2021.9.0',
    'vendor' => '991739',
    'description' => 'Plugin Manager',
    'shop_settings' => true,
    'custom_settings' => true,
    'handlers' => array(
        'routing' => 'routingHook',
        '*' => array(
            array(
                'event' => '/.*/',
                'class' => 'shopPlugmeinPlugin',
                'method' => 'allHook'
            ),
            array(
                'event_app_id' => 'webasyst',
                'event' => 'cli_started',
                'class' => 'shopPlugmeinPlugin',
                'method' => 'cliLog'
            ),
            array(
                'event_app_id' => 'webasyst',
                'event' => 'cli_finished',
                'class' => 'shopPlugmeinPlugin',
                'method' => 'cliLog'
            ),
        )
    )
);
