<?php

require_once __DIR__ . '/../vendor/autoload.php';

class waDbPdomysqlAdapter extends waDbAdapter
{

    /**
     * @var PDO
     */
    protected $handler;

    public function connect($settings)
    {
        $host = $settings['host'];
        $port = isset($settings['port']) ? $settings['port'] : 3306;
        $dbname = $settings['database'];
        $charset = isset($settings['charset']) ? $settings['charset'] : 'utf8';
        try {
            $handler = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=$charset", $settings['user'], $settings['password']);
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
                        $ping = $this->ping();
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
        return $result->fetch(PDO::FETCH_ASSOC);
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
        $s = $this->handler->quote($string);   // MY BAD
        if (strpos($s, "'") === 0) {
            $s = substr($s, 1, -1);
        }
        return $s;
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
        $res = $this->query("DESCRIBE ".$table);
        if (!$res) {
            $this->exception();
        }
        $result = array();
        while ($row = $this->fetch_assoc($res)) {
            $field = array();
            $i = strpos($row['Type'], '(');
            if ($i === false) {
                $field['type'] = $row['Type'];
            } else {
                $field['type'] = substr($row['Type'], 0, $i);
                $field['params'] = substr($row['Type'], $i + 1, strpos($row['Type'], ')') - $i - 1);
                if (strpos($row['Type'], ')') != strlen($row['Type']) - 1) {
                    $field[trim(substr($row['Type'], strpos($row['Type'], ')') + 1))] = 1;
                }
            }
            if ($row['Null'] != 'YES') {
                $field['null'] = 0;
            }

            if ($row['Default'] !== null) {
                $field['default'] = $row['Default'];
            }

            if ($row['Extra'] == 'auto_increment') {
                $field['autoincrement'] = 1;
            }
            $result[$row['Field']] = $field;
        }
        if ($keys) {
            $res = $this->query("SHOW INDEX FROM ".$table);
            if (!$res) {
                $this->exception();
            }
            $rows = array();
            while ($row = $this->fetch_assoc($res)) {
                if ($row['Sub_part']) {
                    $f = array($row['Column_name'], $row['Sub_part']);
                } else {
                    $f = $row['Column_name'];
                }
                if (isset($rows[$row['Key_name']])) {
                    $rows[$row['Key_name']]['fields'][] = $f;
                } else {
                    $rows[$row['Key_name']] = array(
                        'fields' => array($f)
                    );
                    if ($row['Key_name'] != 'PRIMARY' && !$row['Non_unique']) {
                        $rows[$row['Key_name']]['unique'] = 1;
                    }
                    if ($row['Index_type'] == 'FULLTEXT') {
                        $rows[$row['Key_name']]['fulltext'] = 1;
                    }
                }
            }
            $result[':keys'] = $rows;
        }
        return $result;
    }

    public function createTable($table, $data)  // same as waDbMysqliAdapter
    {
        $fields = array();
        foreach ($data as $field_id => $field) {
            if (substr($field_id, 0, 1) != ':') {
                $type = $field['type'].(!empty($field['params']) ? '('.$field['params'].')' : '');
                foreach (array('unsigned', 'zerofill') as $k) {
                    if (!empty($field[$k])) {
                        $type .= ' '.strtoupper($k);
                    }
                }
                if (isset($field['null']) && !$field['null']) {
                    $type .= ' NOT NULL';
                } elseif (in_array(strtolower($field['type']), array('timestamp'))) {
                    $type .= ' NULL';
                }
                if (isset($field['default'])) {
                    if ($field['default'] == 'CURRENT_TIMESTAMP') {
                        $type .= " DEFAULT ".$field['default'];
                    } else {
                        $type .= " DEFAULT '".$field['default']."'";
                    }
                }
                if (!empty($field['autoincrement'])) {
                    $type .= ' AUTO_INCREMENT';
                }
                $fields[] = $this->escapeField($field_id)." ".$type;
            }
        }
        $keys = array();
        foreach ($data[':keys'] as $key_id => $key) {
            if ($key_id == 'PRIMARY') {
                $k = "PRIMARY KEY";
            } else {
                $index_type = '';
                foreach (array('unique', 'fulltext', 'spatial') as $tk) {
                    if (!empty($key[$tk])) {
                        $index_type = strtoupper($tk).' ';
                        break;
                    }
                }
                $k = $index_type."KEY ".$this->escapeField($key_id);
            }
            $key_fields = array();
            foreach ($key['fields'] as $f) {
                if (is_array($f)) {
                    $key_fields[] = $this->escapeField($f[0])." (".$f[1].")";
                } else {
                    $key_fields[] = $this->escapeField($f);
                }
            }
            $keys[] = $k." (".implode(', ', $key_fields).')';
        }
        $sql = "CREATE TABLE IF NOT EXISTS ".$table." (".implode(",\n", $fields);
        if ($keys) {
            $sql .= ", ".implode(",\n", $keys);
        }
        $sql .= ") ENGINE=MyISAM DEFAULT CHARSET=utf8";
        if (!$this->query($sql)) {
            $this->exception();
        }
    }

    protected function exception()
    {
        throw new waDbException($this->error(), $this->errorCode());
    }
}