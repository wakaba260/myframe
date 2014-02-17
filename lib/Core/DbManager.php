<?php

/* * *************************************************************************
 * 	DbManagerクラス
 *
 * 	データベースとの接続を行うPDOオブジェクトのインスタンスの管理を行うクラス。
 * 	また、モデルオブジェクトのインスタンスを生成する役割も持つ。
 *
 * 	Singletonパターンにより、このクラスの保持するPDOオブジェクトのインスタンス
 * 	は1つしか生成されないことを保証する。
 *
 * 	このクラスを使用する場合、DataBaseConfigクラス
 * ************************************************************************** */

class DbManager {

    protected static $dbmanager;
    // PDOオブジェクトのインスタンスを格納するプロパティ
    protected $connection;
    // データベースのデフォルト設定が格納されたプロパティ
    private $default = array(
        'driver' => 'mysql'     // 使用するデータベースのドライバ名
        , 'persistent' => false      // 持続的な接続を使用するか
        , 'database' => 'database_name' // データベース名
        , 'host' => 'localhost'   // ホスト名
        , 'login' => 'user'      // ユーザー名
        , 'password' => 'password'    // パスワード
        , 'encoding' => 'utf8'      // 文字エンコーディングの指定
    );

    protected function __construct(array $param = null) {
        if(!is_null($param)) {
            $this->default = $param;
        }
    }

    public static function getInstance(array $param = null) {
        if (empty(static::$dbmanager)) {
            static::$dbmanager = new DbManager($param);
        }

        return static::$dbmanager;
    }

    /**
     * 	オブジェクトが破棄されるタイミングで接続の開放処理を行う。
     */
    public function __destruct() {
        unset($this->connection);
    }

    /**
     * 	データベースへの接続（PDOオブジェクトのインスタンスの生成）を行う。
     * 	引数には、データベースへの設定を連想配列として引数として渡す。
     * 	引数の記述は、このクラスの$_dbconfigと同じものとする。
     */
    public function getConnection($params = null) {
        $dbconfig = $this->default;

        // 引数がnullでなければ、このクラスのデフォルト設定とマージして上書きする
        if (!is_null($params)) {
            $dbconfig = array_merge($this->default, $params);
        }

        if (empty($this->connection)) {
            $dsn = sprintf(
                    '%s:dbname=%s;host=%s;charset=%s'
                    , $dbconfig['driver']
                    , $dbconfig['database']
                    , $dbconfig['host']
                    , $dbconfig['encoding']
            );

            try {
                if ($dbconfig['persistent']) {
                    $con = new PDO(
                            $dsn, $dbconfig['user'], $dbconfig['password'],
                            array(PDO::ATTR_PERSISTENT => true, PDO::MYSQL_ATTR_INIT_COMMAND => "SET CHARACTER SET `utf8`"));
                } else {
                    $con = new PDO(
                            $dsn, $dbconfig['user'], $dbconfig['password']
                    );
                }
            } catch (PDOException $e) {
                die('データベース接続エラー：' . $e->getMessage());
            }
            $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $con->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
            return $this->connection = $con;
        }

        return $this->connection;
    }

    /**
     * 	public static setConfig($config_name)
     *
     * 	DataBaseConfigの配列名を設定する。
     *
     * 	$param $config_name DataBaseConfigの配列名
     */
    public static function setConfig($config_name) {
        static::$config_name = $config_name;
    }

    /**
     * モデルクラスのインスタンスを作成する
     * 
     * @param string $model_name 作成するモデルクラスのインスタンス
     * @return \model_name|null モデルクラスのインスタンス。またはクラス名の指定が誤っている場合、nullを返す。
     */
    public function get($model_name) {
        // 引数からModelをマッチングするためのパターン文字列
        if (strpos('Model', $model_name)) {
            $model_name .= 'Model';
        }

        $file = APP . '/Model/' . $model_name . '.php';
        if (is_readable($file) && class_exists($model_name)) {
            return new $model_name($this->getConnection());
        } else {
            return null;
        }
    }
}
