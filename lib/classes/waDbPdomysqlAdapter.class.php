<?php

require_once __DIR__ . '/../vendor/autoload.php';

class waDbPdomysqlAdapter extends waDbMysqliAdapter
{

    /**
     * @var PDO
     */
    protected $handler;

    public function connect($settings)
    {
        $host = $settings['host'];
        $port = isset($settings['port']) ? $settings['port'] : ini_get("mysqli.default_port");
        $dbname = $settings['database'];
        $charset = isset($settings['charset']) ? $settings['charset'] : 'utf8';
        try {
            $handler = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=$charset", $settings['user'],$settings['password']);
            $handler->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true); // so it is MYSQL only now
        } catch (PDOException $e) {
            throw new waDbException($e->getMessage());
        }
        return $handler;
    }

    public function query($query)
    {
        $r = @$this->handler->query($query);
        if (!$r) {
            switch ($this->handler->errorCode()) {
                case 2006:
                    // check error MySQL server has gone away
                    $ping = $this->ping();
                    if (!$ping && $this->settings) {
                        $this->close();
                        sleep(1);
                        $this->handler = $this->connect($this->settings);
                        $ping = $this->handler->ping();
                    }
                    $r = $ping ? @$this->handler->query($query) : false;
                    break;
                case 1104:
                    // try set sql_big_selects
                    $this->handler->query('SET SQL_BIG_SELECTS=1');
                    $r = @$this->handler->query($query);
                    break;
            }
        }
        return $r;
    }

    public function ping() {
        $this->handler->exec('SELECT 1');
        return true;
    }

    public function select_db($database)
    {
        return $this->handler->exec("USE $database"); // TODO: check inj
    }

    public function close()
    {
        $this->handler = null;
    }

    /**
     * @param PDOStatement $result
     * @return mixed
     */
    public function num_rows($result)
    {
        return $result->rowCount();
    }

    /**
     * @param PDOStatement $result
     * @return mixed
     */
    public function free($result)
    {
        return $result->closeCursor();
    }

    /**
     * @param PDOStatement $result
     * @return mixed
     */
    public function data_seek($result, $offset)
    {
        return $result->fetch(PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT, $offset);
    }

    /**
     * @param PDOStatement $result
     * @return mixed
     */
    public function fetch_array($result, $mode = self::RESULT_NUM)
    {
        return $result->fetchAll(PDO::FETCH_NUM);
    }

    /**
     * @param PDOStatement $result
     * @return mixed
     */
    public function fetch_assoc($result)
    {
        return $result->fetchAll(PDO::FETCH_ASSOC);
    }

    public function insert_id()
    {
        return $this->handler->lastInsertId();
    }

    public function affected_rows()
    {
        // TODO
    }

    public function escape($string)
    {
        return $this->handler->quote($string);
    }

    /**
     * @return string
     */
    public function error()
    {
        return $this->handler->errorInfo()[2];
    }

    public function errorCode()
    {
        return $this->handler->errorCode();
    }

    public function schema($table, $keys = false)
    {
        // TODO
    }

    public function createTable($table, $data)
    {
        // TODO
    }

    protected function exception()
    {
        throw new waDbException($this->error(), $this->errorCode());
    }
}