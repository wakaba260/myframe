<?php
/**********************************************************************************
*	ValidateSectionクラス
*
*	フォームなどの入力チェックを行う汎用クラス。
*
*	このクラスは1つのチェック項目をラッピングするラッパークラスであり、項目1つに対し、
*	「チェックする項目」「チェック用の正規表現パターン」「文字列の長さ」
*	「数値範囲」「必須かどうか」などの検証属性を与えることが出来る。
*
+	検証属性を与える場合はsetFieldメソッドを用い、isValidメソッドで検証を行う。
*
*	@author 植森康友
*	@lastupdate 2013/12/06 18:00
**********************************************************************************/
class ValidateSection {
	// 検証を行う項目
	private static $_rules = array('null', 'type', 'range', 'length', 'pattern');
	
	// 検証ルールのルール名
	private static $_types = array(
		'japanese', 		// 日本語（ひらがな、カタカナ、漢字）
		'katakana', 		// カタカナ
		'hiragana', 		// ひらがな
		'numeric',  		// 整数値
		'alpha',				// アルファベット
		'date',				  // 日付
		'email',				// e-mailアドレス
		'alphaNumeric',	// 英数字
		'cc',						// クレジットカード
		'phone',				// 電話番号（日本のみ対応）
		'postal',				// 郵便番号（日本のみ対応）
	);
	
	// 検証ルールに対応した正規表現
	private static $_patterns = array(
		'japanese' => '/^[^(\x01-\x7E)^(0-9０－９)]*$/u',
		'katakana' => '/^[ァ-タダ-ヶ]*$/u',
		'numeric' => '/^[0-9]*$/',
		'alpha' => '/^[a-zA-Z]*$/',
		'date' => '/^([12]{1}[90]{1}[0-9]{1}[0-9]{1})([-年\/\.])?(([0]?[1-9]{1})|([1]{1}[12]{1}))([-月\/\.])?(([0]?[1-9]{1})|([1-2]{1}[0-9]{1})|([3]{1}[01]{1}))[日]?$/u',
		'email' => '/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/',
		'alNumeric' => '/^[0-9a-zA-Z]*$/',
		'hiragana' => '/^[ぁ-ゞ]*$/u'
	);
	
	// エラー別のエラーメッセージの配列
	// キーがエラー名、値がエラーメッセージ
	private $_messages = array(
			'default' => ''
		,	'null' => ''
		, 'type' => ''
		, 'range' => ''
		, 'length' => ''
		, 'pattern' => ''
		, 'empty' => ''
	);
	
	// 検証オブジェクトの検証用属性を登録する配列
	private $_fields = array(
			'name' => '' 															// 検証オブジェクトの名前
		, 'rule' => array()													// 検証オブジェクトに適用するルール
		, 'type' => 'none'													// 検証オブジェクトのタイプ
		,	'range' => array ('max' => 0,	'min' => 0) // 検証オブジェクトの数値幅の検証範囲指定
		, 'length' => array('max' => 0,	'min' => 0) // 検証オブジェクトの文字列幅の検証範囲指定
		,	'value' => '' 														// 検証オブジェクトの値
		,	'required' => false 											// 検証オブジェクトが必須項目かどうか
	);
	
	private $_errors = array(); // 検証後のエラーメッセージをセットする配列
	
	/**
	*	コンストラクタ
	*/
	public function __construct() {
	}
	
	/**
	*	setField
	*
	*	オブジェクトに対する検証属性を与える。
	*	検証を行う前に必ずこのメソッドを呼び、ルールを与える必要がある。
	*
	*	引数は以下のパターンのいずれかを用いて渡すことが出来る。
	*
	*	・引数二つで渡す
	*		例）setField('name', 'username')
	*
	*		単一の属性を設定する場合は引数二つで渡すことが出来る。
	*		第一引数が属性名、第二引数が設定値となる。
	*
	*	・引数を連想配列で渡す
	*		例）setField( array('name' => 'username', 'rule' => 'password' ) )
	*		
	*		連想配列で渡した場合、複数の属性を設定することが出来る。
	*		連想配列の場合、キーが属性名、値が設定値となる。
	*
	*	@param オブジェクトに与えたい検証属性（引数二つ、または連想配列）
	*	例外　引数が不正な値、または存在しない属性を指定した場合、InvalidArgumentExceptionが発生する。
	*/
	public function setField() {
		// 引数を取得
		$args = func_get_args();
		
		// 引数が2つなら単一属性として処理
		if(count($args)===2) {
			
			// 第一引数が属性の配列に存在したら処理を行い、存在しなかったら例外をスロー
			if(in_array($args[0], $this->_fields)) {
				// 第一引数をメソッド名としてメソッドを呼び出し処理を委譲
				$method = 'set'.ucfirst($args[0]);
				$this->$method($args[1]);
			}else {
				throw new InvalidArgumentException;
			}
			
		// 引数が1つなら連想配列として処理
		}elseif(count($args)===1) {
			$args = $args[0];
			
			// 引数が連想配列かどうかをチェックし、連想配列でなければ例外をスロー
			if(is_array($args) && !(static::isVector($args))) {
				
				// 連想配列からキーをメソッド名、値を引数としてメソッドを呼び出し処理を委譲
				foreach($args as $key=>$value) {
					$method = 'set'.ucfirst($key);
					$this->$method($value);
				}
			}else {
				throw new InvalidArgumentException;
			}
		// どちらでもなければ例外をスロー
		}else {
			throw new InvalidArgumentException;
		}
	}
	
	/**
	*	static addType($typeName, $pattern)
	*
	*	検証項目のタイプとそのタイプの正規表現を追加するためのメソッド。
	*	タイプは正規表現の検証の際にラベルとして使用される。
	*	正規表現の検証では、タイプ名から正規表現を呼び出し検証を行う。
	*
	*	このメソッドはstaticメソッドなため、追加されたタイプはすべてのインスタンスで使用できるようになる。
	*
	* @param $typeName タイプ名
	*	@param $pattern 正規表現のパターン
	*
	*	例外　第一引数に既存のタイプ名、または第二引数に正規表現として認められない文字列を渡した場合、
	*				InvalidArgumentExceptionがスローされる。
	*/
	public static function addType($typeName, $pattern) {
		
		// 渡されたタイプ名が既存のものでなければ追加処理、既存のものならば例外をスロー
		if(!in_array($typeName, static::$_types)) {
			// 配列にタイプ名を追加
			static::$_types[] = $typeName;
			
			// 正規表現として有効な文字列かどうかを検証し、有効であれば処理を続行、有効でなければ例外をスロー
			if( preg_match('/^[\/]{1}.*[\/]{1}[ismu]?$/', $pattern) ) {
				static::$_patterns[$type] = $pattern;
			}else {
				throw new InvalidArgumentException;
			}
		}else {
			throw new InvalidArgumentException;
		}
	}
	
	/**
	*	setName($name)
	*	
	*	検証項目の名前を設定する。
	*
	*	@param $name 検証項目の名前
	*/
	private function setName($name) {
		$this->_fields['name'] = $name;
	}
	
	/**
	*	setValue($value)
	*	
	*	検証項目の値を設定する。
	*
	*	@param $value 検証項目の値
	*/
	private function setValue($value) {
		$this->_fields['value'] = $value;
	}
	
	/**
	*	setType($type)
	*	
	*	検証項目のタイプを設定する。
	*	タイプは正規表現検証時の正規表現のラベルとして使用される。
	*
	*	@param $type 検証項目のタイプ名
	*	例外　引数のタイプ名が設定されているタイプでなければ、InvalidArgumentExceptionがスローされる。
	*/
	private function setType($type) {
		// 引数のタイプ名が設定されているタイプか判定し、設定されていなければ例外をスロー
		if(in_array($type, static::$_types)) {
			$this->fields['type'] = $type;
		}else {
			throw new InvalidArgumentException;
		}
	}
	
	/**
	*	setMaxLength($len)
	*
	*	検証項目の最大文字列長を設定する。
	*	最大文字列長は文字列長チェックの際に使用される。
	*
	*	最大文字列長を検証しない場合は0を設定すること。
	*	なお、最大文字列長のデフォルトの値は0である。
	*	
	*	@param $len 文字列の最大値
	*	例外　整数値以外を渡した場合、InvalidArgumentExceptionがスローされる。
	*/
	private function setMaxLength($len) {
		// 引数が整数値かどうかチェックし、整数値でなければ例外をスロー
		if(ctype_digit($len)) {
			$this->_fields['length']['max'] = $len;
		}else {
			throw new InvalidArgumentException;
		}
	}
	
	/**
	*	setMinLength($len)
	*	
	*	検証項目の最小文字列長を設定する。
	*	仕様はsetMaxLengthメソッドと同様なためそちらを参照のこと。
	*/
	private function setMinLength($len) {
		// 引数が整数値かどうかチェックし、整数値でなければ例外をスロー
		if(ctype_digit($len)) {
			$this->_fields['length']['min'] = $len;
		}else {
			throw new InvalidArgumentException;
		}
	}
	
	/**
	*	setLength()
	*
	*	文字列長の最大値と最小値を一度に設定する。
	*	このメソッドは引数一つ、引数二つ、連想配列の三つのパターンで設定値を受け取ることが出来る。
	*
	*	・引数一つの場合
	*		引数の値を最大値と最小値の両方に設定する。
	*
	*	・引数二つの場合
	*		引数のうち小さな値を最小値、大きな値を最大値として設定する。
	*
	*	・配列の場合
	*		引数二つの場合と同じ処理を行う。
	*
	*	・連想配列の場合
	*		キーが'max'の値を最大値、'min'の値を最小値として設定する。
	*
	*	その他の注意点はsetMaxLengthメソッド、setMinLengthメソッドを参照のこと。
	*/
	private function setLength() {
		// 引数を配列として受け取る
		$args = func_get_args();
		
		// 引数が2つなら値を最小値と最大値として処理を行う
		if(count($args) === 2) {
			if($args[0]<$args[1]) {
				$min = $args[0];
				$max = $args[1];
			}else {
				$min = $args[1];
				$max = $args[0];
			}
			
			// 数値が二つとも整数値であれば値を設定し、整数値でなければ例外をスロー
			if( ctype_digit($max) && ctype_digit($min) ){
				$this->_fields['length']['max'] = $max;
				$this->_fields['length']['min'] = $min;
			}else {
				throw new InvalidArgumentException;
			}
		
		// 引数が1つなら連想配列として処理を行う
		}elseif(count($args)===1) {
			$args = $args[0];
			
			// 引数が配列なら処理を続行し、配列でなければ最大値と最小値に同じ値を設定する
			if(is_array($args)) {
				// 配列なら引数二つと同じ処理を行う
				if(static::isVector($args)) {
					if($args[0]<$args[1]) {
						$min = $args[0];
						$max = $args[1];
					}else {
						$min = $args[1];
						$max = $args[0];
					}
					
					// 数値が二つとも整数値であれば値を設定し、整数値でなければ例外をスロー
					if( ctype_digit($max) && ctype_digit($min) ){
						$this->_fields['length']['max'] = $max;
						$this->_fields['length']['min'] = $min;
					}else {
						throw new InvalidArgumentException;
					}
				// 連想配列ならキーがmaxの値を最大値、minの値を最小値として設定
				}else {
					$this->_fields['length']['max'] = $args['max'];
					$this->_fields['length']['min'] = $args['min'];
				}
			}else {
				$this->_fields['length']['max'] = $args;
				$this->_fields['length']['min'] = $args;
			}
		}
	}
	
	/**
	*	setMaxRange($len)
	*	
	*	数値幅の最大値を設定する。
	*	数値幅検証の際に使用される。
	*
	*	@param $len 数値幅の最大値
	*	例外　引数が整数値でなければ例外InvalidArgumentExceptionがスローされる
	*/
	private function setMaxRange($len) {
		// 引数が整数であれば値を設定し、引数が整数値でなければ例外をスロー
		if( ctype_digit($len) ) {
			$this->_range['max'] = $len;
		}else {
			throw new InvalidArgumentException;
		}
	}

	/**
	*	setMinRange($len)
	*	
	*	数値幅の最小値を設定する。
	*	数値幅検証の際に使用される。
	*
	*	@param $len 数値幅の最小値
	*	例外　引数が整数値でなければ例外InvalidArgumentExceptionがスローされる
	*/
	private function setMinRange($len) {
		// 引数が整数であれば値を設定し、引数が整数値でなければ例外をスロー
		if( ctype_digit($len) ) {
			$this->_range['min'] = $len;
		}else {
			throw new InvalidArgumentException;
		}
	}
	
	/**
	*	setRange()
	*
	*	数値幅の最大値と最小値を一度に設定する。
	*	このメソッドは引数一つ、引数二つ、連想配列の三つのパターンで設定値を受け取ることが出来る。
	*
	*	・引数一つの場合
	*		引数の値を最大値と最小値の両方に設定する。
	*
	*	・引数二つの場合
	*		引数のうち小さな値を最小値、大きな値を最大値として設定する。
	*
	*	・配列の場合
	*		引数二つの場合と同じ処理を行う。
	*
	*	・連想配列の場合
	*		キーが'max'の値を最大値、'min'の値を最小値として設定する。
	*
	*	その他の注意点はsetMaxRangeメソッド、setMinRangeメソッドを参照のこと。
	*/
	private function setRange() {
		// 引数を配列として受け取る
		$args = func_get_args();
		
		// 引数が2つなら値を最小値と最大値として処理を行う
		if(count($args) === 2) {
			if($args[0]<$args[1]) {
				$min = $args[0];
				$max = $args[1];
			}else {
				$min = $args[1];
				$max = $args[0];
			}
			
			// 数値が二つとも整数値であれば値を設定し、整数値でなければ例外をスロー
			if( ctype_digit($max) && ctype_digit($min) ){
				$this->_fields['range']['max'] = $max;
				$this->_fields['range']['min'] = $min;
			}else {
				throw new InvalidArgumentException;
			}
		
		// 引数が1つなら連想配列として処理を行う
		}elseif(count($args)===1) {
			$args = $args[0];
			
			// 引数が配列なら処理を続行し、配列でなければ最大値と最小値に同じ値を設定する
			if(is_array($args)) {
				// 配列なら引数二つと同じ処理を行う
				if(static::isVector($args)) {
					if($args[0]<$args[1]) {
						$min = $args[0];
						$max = $args[1];
					}else {
						$min = $args[1];
						$max = $args[0];
					}
					
					// 数値が二つとも整数値であれば値を設定し、整数値でなければ例外をスロー
					if( ctype_digit($max) && ctype_digit($min) ){
						$this->_fields['range']['max'] = $max;
						$this->_fields['range']['min'] = $min;
					}else {
						throw new InvalidArgumentException;
					}
				// 連想配列ならキーがmaxの値を最大値、minの値を最小値として設定
				}else {
					$this->_fields['range']['max'] = $args['max'];
					$this->_fields['range']['min'] = $args['min'];
				}
			}else {
				$this->_fields['range']['max'] = $args;
				$this->_fields['range']['min'] = $args;
			}
		}
	}
	
	/**
	*	setMessage()
	*
	*	エラーメッセージを設定する。
	*	エラーメッセージはエラーの種別ごとに設定する必要があり、引数は連想配列で渡すことになる。
	*	
	*	連想配列のキーをエラーのタイプ、値をエラーのメッセージの文字列として渡す。
	*
	*	@param $messages エラーメッセージの連想配列。キーをタイプ、値をメッセージの文字列として渡す。
	*/
	private function setMessage($messages) {
		// 引数が配列かどうか判定し、配列でなければInvalidArgumentExceptionをスロー
		if(is_array($messages)) {
			// 引数が連想配列かどうか判定し、連想配列でなければInvalidArgumentExceptionをスロー
			if(!static::isVector($messages)) {
				// 連想配列からキーと値を取り出し、エラーメッセージをセットする
				foreach($messages as $key=>$message) {
					if(in_array($key, static::$_rules)) {
						$this->$messages[$key] = $message;
					}
				}
			}else {
				throw new InvalidArgumentException;
			}
		}else {
				throw new InvalidArgumentException;
		}
	}
	
	/**
	*	getMessage()
	*
	*	エラーメッセージを連想配列として受け取る。
	*	エラーメッセージはキーがエラーのタイプ、値がメッセージとなっているため、
	*	特定のエラーメッセージを取り出すときはキーにエラーのタイプを指定する。
	*
	*	@return エラーメッセージの連想配列
	*/
	private function getMessage() {
		return $this->_message;
	}
/*	
	private function setDate() {
		$args = func_get_args();
		
		if(count($args)===1) {
			$date = $arg[0];
			if( is_array($date) ) {
				if( is_Vector($args) ) {
					$this->date['year'] = $date['year'];
					$this->date['month'] = $date['month'];
					$this->date['day'] = $date['day'];
				}else {
					$this->date['year'] = $date[0];
					$this->date['month'] = $date[1];
					$this->date['day'] = $date[2];
				}
			}else {
				if($date instanceof DateTime) {
					$date = date_format($date, 'Y-m-d');
				}
				$date = preg_split('/([-\/\.年月日])/', $date);
				if( count($date) === 3 ) {
					$this->date = array( 'year' => $date[0], 'month' => $date[1], 'day' => $date[2] );
				}
			}
		}elseif( count($args) === 3 ) {
			$date = array( 'year' => $args[0], 'month' => $args[1], 'day' => $args[2] );
		}
		throw new InvalidArgumentException;
	}
*/
	private static function is_Vector(array $arr) {
		return array_values($arr) === $arr;
	}
}