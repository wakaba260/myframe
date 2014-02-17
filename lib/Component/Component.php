<?php
/**
 * abstract class Component
 * 
 * 全てのコンポーネントの基底クラスとなる抽象クラス。
 * コンポーネント作成時にこのクラスを継承していない場合、Exceptionが発生する。
 *
 * @author moonoverwalker
 */
abstract class Component {
    
    public function __construct(array $setting = array()) {
        if(!empty($setting)) {
            foreach($setting as $key=>$value) {
                $this->$key = $value;
            }
        }
    }
    
    /**
     * コントローラのbeforeFilterの前に呼び出される。
     */
    public function initialize() {
        
    }
    
    /**
     * コントローラのbeforeFilterの後、アクションハンドラの前に呼び出される。
     */
    public function startup() {
        
    }
    
    /**
     * アクションハンドラの後、ビューの出力の前に呼び出される。
     */
    public function beforeRender() {
        
    }
    
    /**
     * ビューの出力結果が表示される前に呼び出される。
     */
    public function shutdown() {
        
    }
}
