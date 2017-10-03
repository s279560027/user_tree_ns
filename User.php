<?php

class User
{
    static private $_db = null;
    private $_storage;
    private $_id = null;

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    private function __sleep()
    {
    }

    private function __wakeup()
    {
    }

    public static function instance($id)
    {
        if (!self::$_db) {
            throw new Exception('db not set');
        }
        static $instance = false;
        if ($instance === false) {
            $instance = new static();
        }
        $instance->_init($id);
        return $instance;
    }

    private function _init($id)
    {
        $this->_id = (int)$id;
        $this->_storage = [];
        $stmt = self::$_db->prepare('SELECT storage FROM user WHERE id = ?');
        $stmt->execute([$this->_id]);
        $storage = (array) $stmt->fetch(PDO::FETCH_NUM);
        $storage = current($storage);
        if ($storage)
            $this->_storage = self::unserialize($storage);
    }

    static protected function serialize($value)
    {
        array_walk_recursive($value, function ($el) {
            return is_string($el) ? $el : (array)$el;
        });
        return serialize($value);
    }

    static protected function unserialize($value)
    {
        $value = (array)unserialize($value);
        array_walk_recursive($value, function ($el) {
            return is_string($el) ? $el : (array)$el;
        });
        return $value;
    }

    public function get($arg)
    {
        $chunks = explode('\\', $arg);
        $el = $this->_storage;
        foreach ($chunks as $chunk) {
            if (!isset($el[$chunk]))
                return null;
            $el = $el[$chunk];
        }
        return $el;
    }

    public function set($key, $value)
    {
        $chunks = explode('\\', $key);
        $el = &$this->_storage;
        foreach ($chunks as $chunk) {
            if (!isset($el[$chunk]))
                $el[$chunk] = [];
            $el = &$el[$chunk];
        }
        $el = $value;

        $stmt = self::$_db->prepare('INSERT OR REPLACE INTO user(id, storage) VALUES(?, ?)');
        $stmt->execute([$this->_id, self::serialize($this->_storage)]);
    }

    static public function setDb($db)
    {
        self::$_db = $db;
    }
}

try {
    $pdo = new PDO('sqlite:./db');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('CREATE TABLE IF NOT EXISTS user (id int auto_increment PRIMARY KEY, storage text)');
    User::setDb($pdo);
    $user = User::instance(68);
    $user->get('work\new1');
    $user->set('work\new', ['role' => 'new_work_role', 'address' => 'homeland']);
    $user->get('work\new');
    $user->get('work\new\address');
} catch (Exception $e) {
    echo $e->getMessage() . "\n";
    echo 'file: ', $e->getFile() . "\n";
    echo 'line: ', $e->getLine() . "\n";
}


