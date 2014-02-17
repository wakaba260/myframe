<?php
/**
 * class ViewModelAbstract
 * 
 * MVCのビューを扱うモデルクラス。このフレームワークでは、Smartyのラッパークラスとして運用される。
 * Smartyのテンプレートパスの設定、Smartyへの変数の受け渡し、アウトプットバッファリングを用いた出力の取得などを行う。
 * また、ビューの出力に必要なデータベースとのやり取りや出力データの加工などもここの記述することが推奨される。
 * 
 * @author moonoverwalker
 */
class ViewModelAbstract {
    protected $view_dir;
    protected $defaults;
    protected $valiables;
    protected $smarty;
    
    /**
     * 各種初期化とディレクトリのパスの設定を行う。
     * 
     * @param string $view_dir Viewディレクトリのパス
     */
    public function __construct($controller_name) {
        $this->view_dir = Application::getInstance()->getViewDIr();
        $this->defaults = array();
        $this->valiables = array();
        require_once SMARTY_DIR.'Smarty.class.php';
        $this->smarty = new Smarty();
        $this->smarty->template_dir = $this->view_dir.'/templates/'.$controller_name;
        $this->smarty->compile_dir = $this->view_dir.'/templates_c/'.$controller_name;
    }
    
    /**
     * ビューに渡す変数名と値を設定する
     * 
     * @param string $name ビューに渡す変数名
     * @param string $value 変数の値
     */
    public function assign($name, $value) {
        $this->valiables[$name] = $value;
    }
    
    /**
     * HTMLの出力を行い、文字列として返す。
     * 
     * @param string $template_path Smartyのtplファイルのパス
     * @return string HTMLの内容
     */
    public function display($template_path) {
        $request = Request::getInstance();
        $this->assign('webroot', $request->getBaseUrl().'/'.APP_DIR.'/'.WEBROOT_DIR);
        $this->assign('docroot', $request->getBaseUrl());
        foreach($this->valiables as $key=>$value) {
            $this->smarty->assign($key, $value);
        }
        
        ob_start();
        ob_implicit_flush(0);
        
        $this->smarty->display($template_path);
        
        $content = ob_get_clean();
        
        return $content;
    }
    
    public static function get($controller_name) {
        $viewmodel_name = $controller_name.'ViewModel';
        return new $viewmodel_name($controller_name);
    }
}