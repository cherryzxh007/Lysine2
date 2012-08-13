<?php
namespace Lysine;

class Session implements \ArrayAccess {
    static private $instance;

    protected $start;
    protected $data = array();
    protected $snapshot = array();
    protected $is_dirty = false;

    protected function __construct() {
        $this->start = session_status() === PHP_SESSION_ACTIVE;

        if ($this->start)
            $this->data = $_SESSION;

        $this->snapshot = $this->data;
    }

    public function offsetExists($offset) {
        $this->start();
        return isset($this->data[$offset]);
    }

    public function offsetGet($offset) {
        $this->start();
        return $this->data[$offset];
    }

    public function offsetSet($offset, $value) {
        $this->start();
        $this->data[$offset] = $value;
        $this->is_dirty = true;
    }

    public function offsetUnset($offset) {
        $this->start();
        unset($this->data[$offset]);
        $this->is_dirty = true;
    }

    public function commit() {
        if (!$this->start)
            return false;

        if (!$this->is_dirty)
            return true;

        $_SESSION = $this->data;
        session_write_close();

        $this->snapshot = $this->data;
        $this->is_dirty = false;
    }

    public function reset() {
        $this->data = $this->snapshot;
        $this->is_dirty = false;
    }

    public function destroy() {
        $this->start();

        session_destroy();
        $this->reset();
    }

    public function start() {
        if ($this->start)
            return true;

        if (session_status() === PHP_SESSION_DISABLED)
            return false;

        session_start();
        $this->data = $_SESSION;
        $this->snapshot = $_SESSION;

        $_SESSION = $this;
        $this->start = true;
    }

    //////////////////// static method ////////////////////

    static public function initialize() {
        return self::instance();
    }

    static public function instance() {
        return self::$instance
            ?: (self::$instance = $_SESSION = new static);
    }
}