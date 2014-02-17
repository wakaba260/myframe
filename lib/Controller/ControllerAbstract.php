<?php

/* * ******************************************************************************************************
 * 	ControllerAbstract
 *
 * 	コントローラの基底クラスとなる抽象クラス。
 * 	このクラスを継承することで、コントローラは個別のアクションに応じたメソッドとビューを実装すれば、
 * 	コントローラに応じたビューを表示することが出来る。
 *
 * 	アクションに応じたメソッドを実装する際、名前は「アクション名（小文字）Action()」として実装すること。
 *
 * 	@author 植森康友
 * 	@lastupdate 2013/11/21 18:56
 * ****************************************************************************************************** */

abstract class ControllerAbstract {

    protected $controller_name;
    protected $action_method;
    protected $application;
    protected $view;
    protected $request;
    protected $response;
    protected $dbmanager;
    protected $template_path;
    protected $params = array();
    protected $auth_actions = array();
    protected $default_components = array();
    protected $component_objects = array();

    /**
     * コンストラクタ。各プロパティの初期設定を行う。
     * 
     * @param Application $application Applicationクラスのインスタンス
     */
    public function __construct() {
        $this->controller_name = strtolower(str_replace('Controller', '', get_class($this)));
        $this->application = Application::getInstance();
        $this->view = new ViewModel($this->controller_name);
        $this->request = Request::getInstance();
        $this->response = Response::getInstance();
        $this->dbmanager = DbManager::getInstance();
    }

    /**
     * アプリケーションの実行を行う
     * 
     * @param string $action アクション名
     * @param array $params ルーティング定義配列からマッチングを行い得られたパラメータ
     */
    public function run($action, $params = array()) {
        try {
            $this->params = $params;
            $this->action_method = $action . 'Action';
            $this->template_path = $action.'.tpl';
            $action_method = $this->action_method;
            if (!method_exists($this, $action_method)) {
                if ($this->application->isDebugMode()) {
                    $this->forward404();
                } else {
                    $this->redirect();
                }
            }

            $this->initialize();
            $this->beforeFilter();
            $this->startup();

            $this->$action_method($params);
            $this->beforeRender();
            $content = $this->view->display($this->template_path);

            $this->afterFilter();
            $this->shutdown();

            // 表示
            return $content;
        } catch (Exception $e) {
            echo __FILE__ . '　' . __LINE__ . '行目<BR>エラー：' . $e->__toString() . '<BR>';
            echo 'メッセージ：' . $e->getMessage();
        }
    }

    /**
     * 	redirect($url)
     * 	
     * 	引数で渡されたURLにリダイレクトを行う
     *
     * 	@param $url リダイレクト先のURL
     */
    public function redirect($url = null) {
        if (!preg_match('#https?#', $url)) {
            $protocol = $this->request->isSsl() ? 'https://' : 'http://';
            $host = $this->request->getHost();
            $base_url = $this->request->getBaseUrl();

            $url = $protocol . $host . $base_url . $url;
        }

        $this->response->setStatusCode(302, 'Found');
        $this->response->setHttpHeader('Location', $url);
        $this->response->send();
        exit;
    }

    /**
     * 404を記述したHttpNotFoundExceptionを発生させる。
     * 
     * @throws HttpNotFoundException 404を記述したHttpNotFoundException
     */
    public function forward404() {
        throw new HttpNotFoundException('Forwarded 404 page from' . $this->controller_name . '/' . $this->action_method);
    }

    protected function initialize() {
    }

    /**
     * コントローラがビューの出力の前に行う共通処理。
     * 各種コンポーネントのインスタンス作成や、各種設定を行う。
     */
    protected function startup() {
    }

    /**
     * コントローラがビューの出力の後に行う共通処理。
     * セッションの終了やインスタンスの破棄など、必ず行われる処理を記述する。
     */
    protected function beforeRender() {
    }

    protected function shutdown() {
    }

    /**
     * ビューの出力の前に行う共通処理を記述する。
     * 必要に応じてオーバーライドを行い実装する。
     */
    protected function beforeFilter() {
        
    }

    /**
     * ビューの出力の後に行う共通処理を記述する。
     * 必要に応じてオーバーライドを行い実装する。
     */
    protected function afterFilter() {
        
    }

}
