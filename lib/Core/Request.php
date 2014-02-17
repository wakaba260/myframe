<?php

/* * *******************************************************************************
 * 	Requestクラス
 *
 * 	リクエスト情報のカプセル化を行うクラス。
 * 	$_GET、$_POST、$_SERVERなどのスーパーグローバル変数に格納された値はこのクラスを
 * 	通じて取得すること。
 *
 * ******************************************************************************* */

class Request {

    protected static $request;

    public function __isset($name) {
        $arr = explode('_', $name);
        if($arr[0] === 'post') {
            return isset($_POST[$arr[1]]);
        }elseif($arr[0] === 'get') {
            return isset($_GET[$arr[1]]);
        }
    }
    
    public static function getInstance() {
        if (isset(static::$request)) {
            return static::$request;
        } else {
            static::$request = new Request();
            return static::$request;
        }
    }

    /**
     * 	public isPost()
     *
     * 	リクエストがPOSTか判定する
     *
     * 	@return bool リクエストがPOSTならtrue、POSTじゃなければfalse
     */
    public function isPost() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return true;
        }

        return false;
    }

    /**
     * 	public getGet($name = null, $default = null) {
     * 	
     * 	GET情報を取得する。第一引数にはGETの名前を指定する。しなかった場合、GETすべての配列が返る。
     * 	第二引数には、第一引数で指定したGET情報がなかった場合の戻り値を指定する（デフォルトはnull）。
     *
     * 	@param $name GET情報の名前
     * 	@param $default 指定したGET情報がなかった場合の戻り値
     */
    public function getGet($name = null, $default = null) {
        if (isset($_GET[$name])) {
            return $_GET[$name];
        }

        return $default;
    }

    /**
     * 	public getPost($name = null, $default = null )
     * 	POST情報を取得する。第一引数にはPOSTの名前を指定する。しなかった場合、POSTすべての配列が返る。
     * 	第二引数には、第一引数で指定したPOST情報がなかった場合の戻り値を指定する（デフォルトはnull）。
     *
     * 	@param $name POST情報の名前
     * 	@param $default 指定したGET情報がなかった場合の戻り値
     */
    public function getPost($name = null, $default = null) {
        if(is_null($name)) {
            return $_POST;
        }elseif (isset($_POST[$name])) {
            return $_POST[$name];
        }

        return $default;
    }

    /**
     * 	public getHost()
     *
     * 	ホスト情報を取得する。ホスト情報がない場合、Apacheに設定されたホスト名が返る。
     *
     * 	@return $_SERVER['HTTP_HOST']の値。存在しない場合、$_SERVER['SERVER_NAME']。
     */
    public function getHost() {
        if (!empty($_SERVER['HTTP_HOST'])) {
            return $_SERVER['HTTP_HOST'];
        }

        return $_SERVER['SERVER_NAME'];
    }

    /**
     * 	public isSsl()
     *
     * 	HTTPSでアクセスされたかどうかの判定を行う。
     *
     * 	@return bool HTTPSでアクセスされていればtrue、されていなければfalse
     */
    public function isSsl() {
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            return true;
        }

        return false;
    }

    /**
     * 	public getRequestUri()
     *
     * 	リクエストされたURL情報を取得する。
     *
     * 	@return $_SERVER['REQUEST_URI']の値。
     */
    public function getRequestUri() {
        return $_SERVER['REQUEST_URI'];
    }

    /**
     * 	public getBaseUrl()
     *
     * 	ホスト部分より後ろから、フロントコントローラまでのパスを取得する。
     *
     * 	@return string ベースURL
     */
    public function getBaseUrl() {
        $script_name = $_SERVER['SCRIPT_NAME'];

        $pattern = '/^(.*)\/' . APP_DIR . '\/' . WEBROOT_DIR . '\/index\.php$/';
        if (preg_match($pattern, $script_name, $regs)) {
            return $regs[1];
        }

        return '';
    }

    /**
     * 	public getPathInfo()
     * 	
     * 	PATH_INFO（GETパラメータの部分を含まない、ベースURLより後ろの値）を取得する。
     *
     * @return string PATH_INFO
     */
    public function getPathInfo() {
        $base_url = $this->getBaseUrl();
        $request_uri = $this->getRequestUri();
        
        // GETパラメータが含まれる場合、GETパラメータを取り除く
        if (false !== ($pos = strpos($request_uri, '?'))) {
            $request_uri = substr($request_uri, 0, $pos);
        }
        
        // ベースURLを取り除く
        $path_info = (string) str_replace($base_url, '', $request_uri);
        return $path_info;
    }

}
