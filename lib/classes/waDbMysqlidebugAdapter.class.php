<?php

require_once __DIR__ . '/../vendor/autoload.php';

class waDbMysqlidebugAdapter extends waDbMysqliAdapter
{
    public function connect($settings)
    {
        $host = $settings['host'];
        $port = isset($settings['port']) ? $settings['port'] : ini_get("mysqli.default_port");
        $handler = @new \Dzegarra\TracyMysqli\Mysqli($host, $settings['user'], $settings['password'], $settings['database'], $port);
        if ($handler->connect_error) {
            throw new waDbException($handler->connect_error, $handler->connect_errno);
        }

        $charset = isset($settings['charset']) ? $settings['charset'] : 'utf8';
        @$handler->set_charset($charset);
        if (isset($settings['sql_mode'])) {
            $sql = "SET SESSION sql_mode = '".$handler->real_escape_string($settings['sql_mode'])."'";
            @$handler->query($sql);
        }
        return $handler;
    }


}