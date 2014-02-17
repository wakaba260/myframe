<?php

/* * *****************************************************************************************
 * 	Authクラス
 *
 * 	認証と認証情報の管理を行うクラス。
 *
 * 	このクラスを使う場合、データベースにはユーザー名（またはユーザーIDなど）とパスワードを
 * 	同一テーブルにまとめておく必要がある。
 *
 * ***************************************************************************************** */

class Auth extends Session{
    protected $model_name;
    private $model;
    protected static $auth;

    public function __construct(array $setting) {
        parent::__construct($setting);
        if(!strpos($this->model_name, 'Model')) {
            $this->model_name = ucfirst($this->model_name).'Model';
        }
        $this->model = DbManager::getInstance()->get($this->model_name);
    }

    /**
     * 	setAuthenticated($bool)
     *
     * 	ログイン状態の制御を行う。
     * 	引数にtrueを指定した場合はログイン、引数にfalseを指定した場合はログアウト状態となる。
     *
     * 	@param $bool ログイン状態の成否
     */
    public function setAuthenticated($bool) {
        $this->regenerate();

        $this->set('_authenticated', (bool) $bool);
    }

    /**
     * 	isAuthenticated()
     *
     * 	ログインしているかどうかを判定する
     *
     * 	@return bool ログイン中ならtrue、ログイン中でなければfalseを返す
     */
    public function isAuthenticated() {
        return $this->get('_authenticated', false);
    }

    /**
     * authenticate(array $infomation)
     *
     * 	ユーザー情報の格納されたテーブルオブジェクトのクラス名と認証情報の連想配列で渡すことで認証を行う。
     * 	引数は以下のように渡す必要がある。
     *
     * 	・クラス名
     * 		ユーザーの情報が格納されているModelAbstractのサブクラスであるオブジェクトのクラス名を渡す。
     * 	
     * 	・キーと値
     * 		キーはデータベースのカラム名、値はユーザーからPOSTされた値とする
     *
     * 		例）array('user_name' => 'tanaka', 'password' => 'abc123')
     *
     * 	@param ModelAbstract $model ModelAbstractのインスタンス
     * 	@param $infomation 認証情報の連想配列
     * 	@return bool 認証に成功すればtrue、認証に失敗すればfalse
     */
    public function authenticate(array $infomations) {
        $conditions = array();
        $infomation = $infomations['infomation'];
        foreach ($infomation as $key => $value) {
            $where[$key] = $value;
        }
        $conditions['where'] = $where;

        $result = $this->model->find($conditions);

        if (count($result) > 0) {
            return true;
        }

        return false;
    }
    
    public static function getInstance(array $setting = array()) {
        if(!isset(static::$auth)) {
            static::$auth = new Auth($setting);
        }
        
        return static::$auth;
    }
}
