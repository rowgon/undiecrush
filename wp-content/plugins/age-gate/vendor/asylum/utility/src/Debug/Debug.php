<?php

namespace Asylum\Utility\Debug;


class Debug
{
    /**
     * Dump and die
     *
     * @param mixed $data
     * @return self
     */
    public function dd($data)
    {
        if ($this->run()) {
            $this->sendHeader();
            dd($data);
        }
        return $this;
    }

    /**
     * Send a raw HTTP Header
     *
     * @param string $type
     * @return void
     */
    public function sendHeader($type = "content-type: text/html")
    {
        header($type);
    }

    /**
     * Run wp_die
     *
     * @param mixed $data
     * @return self
     */
    public function die($data)
    {
        if ($this->run()) {
            if (is_iterable($data) || is_object($data) || is_array($data)) {
                wp_die(print_r($data, 1));
            } else {
                wp_die($data);
            }
        }
        return $this;
    }

    /**
     * Dump data
     *
     * @param mixed ...$data
     * @return self
     */
    public function dump(...$data)
    {
        if ($this->run()) {
            dump(...$data);
        }
        return $this;
    }

    /**
     * Error log data
     *
     * @param mixed $data
     * @return self
     */
    public function log($data)
    {
        if ($this->run()) {
            if (is_iterable($data) || is_object($data) || is_array($data)) {
                error_log(print_r($data, 1));
            } else {
                error_log($data);
            }
        }
        return $this;
    }

    /**
     * Dump if condition true
     *
     * @param mixed ...$data
     * @return void
     */
    public function dumpWhen($callback, ...$data)
    {
        if ($this->run() && $this->evalCallback($callback)) {
            dump(...$data);
        }
        return $this;
    }

    /**
     * Dump if condition true
     *
     * @param mixed ...$data
     * @return void
     */
    public function ddWhen($callback, ...$data)
    {
        if ($this->run() && $this->evalCallback($callback)) {
            dd(...$data);
        }
        return $this;
    }

    /**
     * Dump if condition false
     *
     * @param mixed ...$data
     * @return void
     */
    public function dumpWhenNot($callback, ...$data)
    {
        if ($this->run() && $this->evalCallback($callback, '!=')) {
            dump(...$data);
        }
        return $this;
    }

    /**
     * Dump if condition false
     *
     * @param mixed ...$data
     * @return void
     */
    public function ddWhenNot($callback, ...$data)
    {
        if ($this->run() && $this->evalCallback($callback, '!=')) {
            dd(...$data);
        }
        return $this;
    }

    /**
     * Test if log should run
     *
     * @return bool
     */
    private static function run()
    {
        return ($_ENV['APP_ENV'] ?? false) === 'local' || ($_ENV['APP_ENV'] ?? false) === 'development';
    }

    private function evalCallback($callback, $operator = '=', $value = true)
    {
        if (!is_callable($callback)) {
            $this->dd('Invalid Callback');
        }

        switch ($operator) {
            case "=":  return $callback() == $value;
            case "!=": return $callback() != $value;
            case ">=": return $callback() >= $value;
            case "<=": return $callback() <= $value;
            case ">":  return $callback() >  $value;
            case "<":  return $callback() <  $value;
            default:       return true;
        }
    }
}