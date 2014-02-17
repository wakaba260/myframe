<?php

class Application {

    protected $debug = false;
    protected $request;
    protected $response;
    protected $session;
    protected $dbmanager;
    protected $router;
    protected static $application;
    
    public static function getInstance($debug = false) {
        if(isset(static::$application)) {
            return static::$application;
        }else {
            static::$application = new Application($debug);
            return static::$application;
        }
    }

    /**
     * デバッグモードの設定、各オブジェクトの初期化、構成の読み込みを行う
     * 
     * @param bool $debug デバッグモードを設定するか
     */
    protected function __construct($debug = false) {
        $this->setDebugMode($debug);
        $this->initialize();
        $this->configure();
    }

    /**
     * デバッグモードの設定を行う。
     * 
     * @param bool $debug デバッグモードを設定するか
     */
    protected function setDebugMode($debug) {
        if ($debug) {
            $this->debug = true;
            ini_set('display_errors', 1);
            error_reporting(-1);
        } else {
            $this->debug = false;
            ini_set('display_errors', 0);
        }
    }

    /**
     * オブジェクトの初期化を行う。
     */
    protected function initialize() {
        $this->request = Request::getInstance();
        $this->response = Response::getInstance();
        
        $dbconfig = array();
        $file = APP. '/Config/dbconfig.php';
        if(is_readable($file)) {
            require $file;
        }
        $this->dbmanager = DbManager::getInstance($dbconfig);
        
        $this->router = Router::getInstance($this->registerRoutes());
    }

    protected function configure() {
        
    }

    /**
     * ルートディレクトリへのパスを返す。
     * 
     * @return string ルートディレクトリのパス
     */
    public function getRootDir() {
        return APP;
    }

    /**
     * ルーティング定義配列を返す。
     * 
     * @return array ルーティング定義配列
     */
    protected function registerRoutes() {
        $file = APP.'/Config/routes.php';
        $routes = array();
        
        if(is_readable($file)) {
            require $file;
        }
        
        return $routes;
    }

    /**
     * デバッグモードかどうかを返す。
     * 
     * @return bool デバッグモードならtrue、デバッグモードでなければfalse
     */
    public function isDebugMode() {
        return $this->debug;
    }

    /**
     * Requestクラスのインスタンスを返す
     * 
     * @return Request Requestクラスのインスタンス
     */
    public function getRequest() {
        return $this->request;
    }

    /**
     * Responseクラスのインスタンスを返す
     * 
     * @return Response Responseクラスのインスタンス
     */
    public function getResponse() {
        return $this->response;
    }

    /**
     * Sessionクラスのインスタンスを返す
     * 
     * @return Session Sessionクラスのインスタンス
     */
    public function getSession() {
        return $this->session;
    }

    /**
     * DbManagerクラスのインスタンスを返す
     * 
     * @return DbManager DbManagerクラスのインスタンス
     */
    public function getDbManager() {
        return $this->dbmanager;
    }

    /**
     * コントローラのディレクトリパスを取得する
     * 
     * @return string コントローラのディレクトリパス
     */
    public function getControllerDir() {
        return $this->getRootDir() . '/Controller';
    }

    /**
     * ビューのディレクトリパスを取得する
     * 
     * @return string ビューのディレクトリパス
     */
    public function getViewDir() {
        return $this->getRootDir() . '/View';
    }

    /**
     * モデルのディレクトリパスを取得する
     * 
     * @return string モデルのディレクトリパス
     */
    public function getModelDir() {
        return $this->getRootDir() . '/Model';
    }

    /**
     * WEBルートのディレクトリパス「を取得する
     * 
     * @return string WEBルートのディレクトリパス
     */
    public function getWebDir() {
        return $this->getRootDir() . 'webroot';
    }

    /**
     * コントローラのインスタンスを取得し、アクションの実行を行う
     * 
     * @throws HttpNotFoundException ルーティング定義配列からパラメータが生成できない、またはコントローラが見つからない
     */
    public function run() {
        try {
            $params = $this->router->resolve($this->request->getPathInfo());

            // ルーティング定義配列がルーティング定義にマッチングしない場合、HttpNotFoundExceptionが発生する
            if ($params === false) {
                throw new HttpNotFoundException('No route found for' . $this->request->getPathInfo());
            }
            $controller = $params['controller'];
            $aciton = $params['action'];
            $this->runAction($controller, $aciton, $params);
        } catch (HttpNotFoundException $e) {
            $this->render404Page($e);
        }

        // レスポンスの送信
        $this->response->send();
    }

    /**
     * コントローラを呼び出し、アクションを実行する
     * 
     * @param string $controller_name コントローラ名（'Controller'までの部分）
     * @param string $aciton アクション名
     * @param array $params ルーティング定義配列より得られたパラメータの配列
     * @throws HttpNotFoundException コントローラが見つからなかった場合
     */
    public function runAction($controller_name, $aciton, $params = array()) {
        $controller_class = ucfirst($controller_name).'Controller';

        $controller = $this->findController($controller_class);
        // コントローラが見つからなかった場合、HttpNotFoundExceptionが発生する
        if ($controller === false) {
            throw new HttpNotFoundException($controller_class . ' is not found');
        }

        $content = $controller->run($aciton, $params);
        $this->response->setContent($content);
    }

    /**
     * コントローラのクラス名からコントローラのphpファイルを探して読み込み、インスタンスを返す。
     * 
     * @param string $controller_class コントローラのクラス名（先頭大キャメル）
     * @return \controller_class|boolean コントローラクラスのインスタンス。存在しなかった場合、false。
     */
    protected function findController($controller_class) {
        $controller_file = $this->getControllerDir() . '/' . $controller_class . '.php';
        if (!is_readable($controller_file)) {
            return false;
        } else {
            require_once $controller_file;

            if (!class_exists($controller_class)) {
                return false;
            }
        }

        return new $controller_class($this);
    }
    
    protected function render404page($e) {
        $this->response->setStatusCode(404, 'Not Found');
        $message = $this->isDebugMode()? $e->getMessage() : 'Page not Found.';
        
        $this->response->setContent($message);
    }
}
