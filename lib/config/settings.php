<?php

return [
    'debugbar' => [
        'title' => 'Включить панель отладки',
        'description' => '',
        'control_type' => waHtmlControl::CHECKBOX,
        'value' => 0,
    ],
    'mysql' => [
        'title' => 'Включить обработку запросов к базе данных',
        'description' => '',
        'control_type' => waHtmlControl::CHECKBOX,
        'value' => 1,
    ],
    'long_queries' => [
        'title' => 'Показывать только долгие запросы к базе данных',
        'description' => '',
        'control_type' => waHtmlControl::CHECKBOX,
        'value' => 1,
    ],
    'long_events' => [
        'title' => 'Показывать только долгие события',
        'description' => '',
        'control_type' => waHtmlControl::CHECKBOX,
        'value' => 1,
    ],
];