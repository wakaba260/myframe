<?php
/**
 * class AppController
 *
 * 全てのコントローラの継承先となる基本的なコントローラ。
 * コントローラに共通する処理や設定はこのクラスに記述する。
 * 
 * @author moonoverwalker
 */
class AppController extends ControllerAbstract {
    public function indexAction() {
        
    }
    
    public function beforeFilter() {
        parent::beforeFilter();
        $this->auth = Auth::getInstance(array('model_name' => 'AuthenticationsModel'));
    }
}
