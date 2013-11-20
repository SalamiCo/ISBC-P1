<?php

class Cache {
  
  private static $caches = array();

  private $data = array();

  public static function getCache ($name) {
    if (!isset(self::$caches[$name])) {
      self::$caches[$name] = new Cache($name);
    }

    return self::$caches[$name];
  }

  public function __construct ($name) {
    $this->name = $name;
  }

  public function has ($name) {
    return isset($this->data[$name]);
  }

  public function get ($name) {
    if (!$this->has($name)) {
      return null;
    }

    return $this->data[$name];
  }

  public function set ($name, $value) {
    $this->data[$name] = $value;
  }

  public function load () {
    $file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $this->name;
    if (is_file($file)) {
      $this->data = array_merge($this->data, unserialize(gzdecode(file_get_contents($file))));
    }
  }

  public function save () {
    $file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $this->name;
    file_put_contents($file, gzencode(serialize($this->data)));
  }

}