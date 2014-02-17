<?php
/************************************************************************************
*	ClassLoader
*
*	オートロードに関する処理をまとめたクラス。
*	まだ読み込まれていないクラスのオブジェクトが作成されたとき、
*	registerDir()メソッドで登録したディレクトリからクラスファイルの読み込みを行う。
************************************************************************************/
class ClassLoader {
	// 登録されたディレクトリの配列
	protected $dirs;

	/**
	*	register()
	*
	*	オートローダを登録する。
	*	このメソッドをコールする前に、registerDir()メソッドで読み込むディレクトリを指定しておく必要がある。
	*/
	public function register() {
		spl_autoload_register(array($this, 'loadClass'));
	}
	
	/**
	*	registerDir($dir)
	*
	*	オートローダするディレクトリの登録を行う。
	*	このメソッドによりディレクトリを登録したのちにregister()メソッドを呼ぶことで、
	*	登録されたディレクトリに存在するファイルはrequireで読み込む必要がなくなる。
	*
	*	@param $dir このメソッドをコールするファイルから登録するディレクトリまでのパス
	*/
	public function registerDir($dir) {
		$this->dirs[] = $dir;
	}
	
	/**
	*	loadClass($class)
	*
	*	オートロードが実行された際にクラスファイルを読み込む処理を行う
	*
	* @param $class クラス名
	*/
	public function loadClass($class) {
		// フィールドからディレクトリを読み込む
		foreach($this->dirs as $dir) {
			// ファイル名が見つかったらファイルを読み込み、returnでループを中断する
			$file = $dir.'/'.$class.'.php';
			if(is_readable($file)) {
				require $file;
				
				return;
			}
		}
	}
}