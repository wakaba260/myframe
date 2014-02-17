<?php
class Response {
	// クライアントへ実際に返す内容を受け取るプロパティ
	protected $content;
	
	// ステータスコードの設定値
	protected $status_code = 200;
	
	// ステータステキストの設定文字列
	protected $status_text = 'OK';
	
	// HTTPヘッダを格納するプロパティ
	protected $http_headers = array();
	
        protected static $response;


        public static function getInstance() {
            if(!isset(static::$response)) {
                static::$response = new Response();
            }
            return static::$response;
        }

                /**
	*	public send()
	*
	*	HTTPヘッダ、およびクライアントへの出力内容を送信する
	*/
	public function send() {
		// HTTPヘッダを送信
		header('HTTP/1.1' . $this->status_code . ' ' . $this->status_text);
		
		foreach($this->http_headers as $name=>$value) {
			header($name. ':' . $value);
		}
		
		echo $this->content;
	}
	
	/**
	*	public setContent($content)
	*
	*	送信内容をプロパティへと設定する
	*
	*	@param $content 送信内容
	*/
	public function setContent($content) {
		$this->content = $content;
	}
	
	/**
	*	public setStatusCode($status_code,$status_text = '')
	*
	*	ステータスコードをプロパティに設定する
	*
	*	@param $status_code ステータスコード
	* @param $status_text ステータステキスト
	*/
	public function setStatusCode($status_code, $status_text = '') {
		$this->status_code = $status_code;
		$this->status_text = $status_text;
	}
	
	/**
	*	public setHttpHeader($name, $value)
	*
	*	HTTPヘッダに出力するHTTPヘッダーを格納する。
	*
	*	@param $name HTTPヘッダ名
	*	@param $value ヘッダの内容
	*/
	public function setHttpHeader($name, $value) {
		$this->http_headers[$name] = $value;
	}
}