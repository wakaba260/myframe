<?php

/**
 * class Cookie
 *
 * @author moonoverwalker
 */
class Cookie extends Component {

    protected static $instance;
    protected $config = array(
        'expiration' => 0,
        'path' => '/',
        'domain' => null,
        'secure' => false,
        'httponly' => false,
    );

    public function __construct(array $setting = array()) {
        parent::__construct($setting);
        $file = APP . '/config/cookie.php';
        if (is_readable($file)) {
            require $file;
            array_merge($this->config, $config);
        }
    }

    public function __isset($name) {
        if (isset($_COOKIE[$name])) {
            return true;
        } else {
            return false;
        }
    }

    public function set($name, $value, $expiration = null, $path = null, $domain = null, $secure = null, $httponly = null) {
        if (empty($name) || empty($value)) {
            throw new InvalidArgumentException;
        }
        if (is_null($expiration)) {
            $expiration = $this->config['expiration'];
        }
        if (is_null($path)) {
            $path = $this->config['path'];
        }
        if (is_null($domain)) {
            $domain = $this->config['domain'];
        }
        if (is_null($secure)) {
            $secure = $this->config['secure'];
        }
        if (is_null($httponly)) {
            $httponly = $this->config['httponly'];
        }
        return setcookie($name, $value, $expiration, $path, $domain, $secure, $httponly);
    }

    public function get($name = null, $default = null) {
        if (isset($_COOKIE[$name])) {
            return $_COOKIE[$name];
        }

        return $default;
    }

    public function delete($name, $path = null, $domain = null, $secure = null, $httponly = null) {
        if (is_null($path)) {
            $path = $this->config['path'];
        }
        if (is_null($domain)) {
            $domain = $this->config['domain'];
        }
        setcookie($name, '', time() - 3600, $path, $domain, $secure, $httponly);
    }

    public static function getInstance(array $setting = array()) {
        if (!isset(static::$instance)) {
            static::$instance = new Cookie($setting);
        }

        return static::$instance;
    }

}
