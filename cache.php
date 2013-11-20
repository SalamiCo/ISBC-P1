<?php

class Cache {
  const MAX_STORED_ITEMS = 10000;

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
    if (!isset($this->data[$name])) {
      return null;
    }

    $this->data[$name]['count']++;
    return $this->data[$name]['item'];
  }

  public function set ($name, $value) {
    $this->data[$name] = array(
      'item'  => $value,
      'count' => isset($this->data[$name]['item'])
                 && $this->data[$name]['item'] == $value
               ? $this->data[$name]['count']
               : 0
    );
  }

  public function load () {
    $file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $this->name;

    if (is_file($file)) {
      $this->data = array_merge($this->data, unserialize(gzdecode(file_get_contents($file))));
    }
  }

  public function save () {
    echo count($this->data) . ' | ';
    if (count($this->data) > self::MAX_STORED_ITEMS) {
      $vals = array_values($this->data);
      usort($vals, function ($a, $b) {
        return $a['count'] < $b['count']
             ? 1
             : ($a['count'] > $b['count']
               ? -1
               : 0);
      });
      $min = $vals[(self::MAX_STORED_ITEMS * 2) / 3]['count'];
      echo '(' . $min . ') | ';

      foreach ($this->data as $i=>$v) {
        if ($v['count'] <= $min) {
          unset($this->data[$i]);
        }
      }
    }
    echo count($this->data);

    $file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $this->name;
    file_put_contents($file, gzencode(serialize($this->data)));
  }

}