<?php
namespace Dzegarra\TracyMysqli;

class Mysqli extends \mysqli
{
    /**
     * Logged queries.
     * @var array
     */
    protected static $log = [];

    /**
     * Relay all calls.
     *
     * @param string $name      The method name to call.
     * @param array  $arguments The arguments for the call.
     *
     * @return mixed The call results.
     */
    public function __call($name, array $arguments)
    {
        return call_user_func_array(
            array($this, $name),
            $arguments
        );
    }

    /**
     * @see \mysqli::query
     */
    public function query($query, $resultmode = MYSQLI_STORE_RESULT)
    {
        $start = microtime(true);
        $result = parent::query($query, $resultmode);
        $time = microtime(true) - $start;
        $this->addLog($query, $time);
        $this->addSlowLog($query, $time);
        return $result;
    }

    /**
     * Add query to logged queries.
     * @param string $query
     */
    public function addLog($query, $time)
    {
        $entry = [
            'statement' => $query,
            'time' => $time
        ];
        array_push(self::$log, $entry);
    }

    public function addSlowLog($query, $time)
    {
        if ($time > 0.5) {
            $classes = [];
            foreach (debug_backtrace(0, 15) as $call) {
                if (empty($call['class'])
                    || stripos($call['class'], 'mysql')
                    || stripos($call['class'], 'model')) {
                    continue;
                }
                if ($call['class'] == 'Smarty_Internal_TemplateBase') {
                    $classes[] = $call['class'] . " ({$call['args'][0]})";
                } else {
                    $classes[] = $call['class'];
                }
            }
            $debug = implode(' -> ', $classes);
            \waLog::log(round($time, 2) . "s - $debug \n$query", 'mysql-slow.log');
        }
    }

    /**
     * Return logged queries.
     * @return array Logged queries
     */
    public static function getLog()
    {
        return self::$log;
    }
}
