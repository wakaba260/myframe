<?php

class Session extends Component{

    protected static $sessionStarted = false;    // セッションが開始されているかを保持するフィールド
    protected static $sessionIdRegnerated = false; // セッションIDが新しく発行されているかを保持するフィールド
    protected static $session;

    /**
     * 	コンストラクタ
     *
     * 	オブジェクトの生成と同時にセッションを開始する
     */

    public function __construct(array $setting = array()) {
        parent::__construct($setting);
        if (!self::$sessionStarted) {
            session_start();

            self::$sessionStarted = true;
        }
    }
    
    public function __isset($name) {
        return isset($_SESSION[$name]);
    }
    
    /**
     * 	public set($name,$value)
     *
     * 	セッション変数に値をセットする
     * 	
     * 	@param $name セッション変数名
     * 	@param $value セットする値
     */
    public function set($name, $value) {
        $_SESSION[$name] = $value;
    }

    /**
     * 	public get($name,$default)
     *
     * 	セッション変数から値を取得する
     * 	
     * 	@param $name 取得したいセッション変数名
     * 	@param $value セッション変数がなかった場合の戻り値
     */
    public function get($name = null, $default = null) {
        if (isset($_SESSION[$name])) {
            return $_SESSION[$name];
        }

        return $default;
    }

    /**
     * 	public clear()
     *
     * 	セッション変数を全消去する
     */
    public function clear() {
        $session->get = array();
    }

    /**
     * 	public regenerate($destroy = true)
     *
     * 	セッションIDを新しく発行する
     * 	
     * 	@param $destroy 古いIDに紐づけられたセッションを削除するかどうか
     */
    public function regenerate($destroy = true) {
        // スクリプト中でセッションIDの再発行が行われてなければセッションIDの再発行を行う
        if (!self::$sessionIdRegnerated) {
            session_regenerate_id($destroy);

            self::$sessionIdRegnerated = true;
        }
    }
    
    public static function getInstance() {
        if(!isset(static::$session)) {
            $classname = get_called_class();
            static::$session = new $classname;
        }
        
        return static::$session;
    }
}
