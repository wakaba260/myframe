<?php

/* * ************************************************************************
 * 	Routerクラス
 *
 * 	ルーティング定義配列とPATH_INFOを渡すことで、ルーティングパラメータを
 * 	特定する役割を持つクラス。
 *
 * ************************************************************************ */

class Router {

    private static $router;
    // ルーティング定義配列を正規表現にコンパイルした配列を受け取るフィールド
    protected $routes;

    public static function getInstance(array $defiinitions = array()) {
        if (isset(static::$router)) {
            return static::$router;
        } else {
            $file = APP . '/Config/routes.php';
            if (is_readable($file)) {
                require $file;
                $defiinitions = array_merge($routes, $defiinitions);

                static::$router = new Router($defiinitions);
                return static::$router;
            }
        }
    }

    /**
     * 	__construct($definitions)
     *
     * 	コンストラクタではルーティング定義を行うため、
     * 	引数にはルーティング定義配列を渡す。
     *
     * 	キーにPATH_INFOの定義、値にコントローラ名とアクション名を渡す。
     * 	キーのPATH_INFOにコロン（:）が設定されていた場合、その部分は動的ルーティングパラメータを表す。<br>
     * 	動的ルーティングパラメータに指定された部分は、コロンの後に続くパラメータに指定された部分が値として格納される。
     *
     * 	例）
     * 	$definitions = array (
     * 		'/' => array('controller' => 'index', 'action' => 'index'),
     * 		'/:controller' => array('action' => 'index'),
     * 		'/item/:action' => array('contoroller' => 'item')
     * 	);
     */
    protected function __construct(array $definitions) {
        $this->routes = $this->compileRoutes($definitions);
    }

    /**
     * 	public compileRoutes($definitions)
     *
     * 	ルーティング定義配列を正規表現に対応する形にコンパイルする。
     *
     * 	@param $definitions ルーティング定義配列の配列
     * 	@return $routes コンパイル後のルーティング定義配列
     */
    public function compileRoutes(array $definitions) {
        $routes = array();

        foreach ($definitions as $url => $params) {
            // キーのURLをスラッシュで分割
            $tokens = explode('/', ltrim($url, '/'));
            foreach ($tokens as $i => $token) {
                // トークンにコロンが含まれていれば正規表現対応型にコンパイル
                if (0 === strpos($token, ':')) {
                    $name = substr($token, 1);
                    $token = '(?P<'.$name.'>[^/]+)';
                }
                $tokens[$i] = $token;
            }

            // 分割していたトークンを正規表現パターンとして結合し、パラメータと組み合わせて保存
            $pattern = '/' . implode('/', $tokens);
            $routes[$pattern] = $params;
        }

        return $routes;
    }

    /**
     * 	public resolve($path_info)
     *
     * 	コンパイルしたルーティング定義配列と、PATH_INFOとのマッチングを行い、結果のパラメータを連想配列として返す
     *
     * @param $path_info PATH_INFOの文字列
     * @return $mixed マッチング後の連想配列。マッチング失敗時はfalseが返される。
     */
    public function resolve($path_info) {
        if ('/' !== substr($path_info, 0, 1)) {
            $path_info = '/' . $path_info;
        }

        foreach ($this->routes as $pattern => $params) {
            if (preg_match('#^' . $pattern . '#', $path_info, $matches)) {
                $params = array_merge($params, $matches);
                $params['param'] = explode('/', str_replace($params, '', $path_info));
                return $params;
            }
        }

        return false;
    }

}
