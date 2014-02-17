<?php

/* * ***************************************************************************************************
 * 	class ModelAbstract
 * 
 * 	このクラスはテーブルをActive Recordパターンでオブジェクト化したものであり、
 * 	汎用的なSELECT文を構築してデータベースへの接続を行う汎用的な機能を提供する。
 *
 * 	このクラスは抽象クラスであり、このクラスの提供する機能を利用する場合はテーブルに合わせて
 * 	サブクラスを作成する必要がある。
 * 	
 *
 * 	また、このクラスはINSERT、UPDATE、DELETE機能を持たないため、
 * 	CRUD機能を全て使いたい場合はこのクラスではなくこのクラスのサブクラスであるDataModelAbstractを継承すること。
 *
 * 
 * 	@author 植森康友
 * 	@lastupdate 2013/11/21 15:33
 *
 * 	参考サイト：http://blog.tojiru.net/article/277021312.html
 * **************************************************************************************************** */

abstract class FindModelAbstract {

    protected $_db;
    protected $_dbName;
    protected $_tableName;

    // データ型を表す定数。
    const
            BOOLEAN = 'boolean',
            INTEGER = 'integer',
            DOUBLE = 'double',
            FLOAT = 'double',
            STRING = 'string',
            DATETIME = 'datetime';

    // このドメインモデルのインスタンスがデータを渡すための配列
    protected $_data = array();
    // 主キーを定義するスタティック変数
    protected $_primary;
    // このクラスのテーブルの持つフィールド名とデータ型をセットする配列
    protected static $_schema = array();

    /**
     * 	コンストラクタ
     *
     * 	このオブジェクトを作成すると、自動的に対応するテーブルを設定する。
     * 	複数のテーブルを扱いたい場合、setTable()メソッドを使うこと。
     */
    function __construct() {
        $this->setUp();
    }

    /**
     * 	__get($prop)
     *
     * 	このクラスが持つフィールドの値を取得するためのマジックメソッド
     * 	フィールド名をこのクラスのプロパティとして呼び出すことで、そのドメインモデルの持つフィールドの値を呼び出すことが出来る。
     * 	
     * 	その場合、このクラスのインスタンスにまだ値が設定されていなかった場合、nullが返される。
     * 	また、存在しないフィールド名を呼び出した場合、InvalidArgumentExceptionがスローされる。
     *
     * 	@param prop フィールド名
     * 	@return フィールドの値。
     * 	値が設定されていなかった場合はnull、その名前のフィールドが存在しない場合はInvalidArgumentExceptionがスローされる。
     */
    function __get($prop) {
        if (isset($this->_data[$prop])) {
            return $this->_data[$prop];
        } elseif (isset(static::$_schema[$prop])) {
            return null;
        } else {
            throw new InvalidArgumentException;
        }
    }

    /**
     * 	__isset($prof)
     *
     * 	フィールドの値をisset関数を用いてチェックするためのマジックメソッド。
     * 	
     * 	@return フィールドの値があればtrue、なければfalse
     */
    function __isset($prop) {
        return isset($this->_data[$prop]);
    }

    /**
     * 	__set($prof, $val)
     *
     * 	このクラスにフィールドの値をセットするためのマジックメソッド。
     * 	プロパティに引数のフィールド名がなかった場合、InvalidArgumentExceptionがスローされる。
     *
     * 	@param $prof フィールドの名前
     * 	@param $val	フィールドの値
     * 	@return 
     */
    function __set($prop, $val) {

        // スキーマにキーがなかった場合、
        if (!(isset(static::$_schema[$prop]))) {
            echo $prop;
            var_dump(static::$_schema);
            throw new InvalidArgumentException;
        }

        // スキーマと第二引数のデータ型を取得する
        $schema = static::$_schema[$prop];
        $type = gettype($val);

        // 指定されたフィールドのデータ型がDATETIMEならDATETIMEのインスタンスとしてフィールドをセットする
        if ($schema === static::DATETIME) {
            if ($val instanceof DateTime) {
                $this->_data[$prop] = $val;
            } else {
                $this->_data[$prop] = new DateTime($val);
            }
            return;
        }

        // スキーマと第二引数のデータ型を比較し、同じならそのままセットする
        if ($type === $schema) {
            $this->_data[$prop] = $val;
            return;
        }

        // スキーマと第二引数のデータ型が違った場合、スキーマのデータ型にあわせてキャストを行ってセットする
        switch ($schema) {
            case static::BOOLEAN:
                return $this->_data[$prop] = (bool) $val;
            case static::INTEGER:
                return $this->_data[$prop] = (int) $val;
            case static::DOUBLE:
                return $this->_data[$prop] = (double) $val;
            case static::STRING:
            default:
                return $this->_data[$prop] = (string) $val;
        }
    }

    /**
     * 	static _decorate(PDOStatement $stmt)
     * 	
     * 	PDOStatementのオブジェクトを渡すことで、そのオブジェクトのフェッチモードをFETCH_CLASSに変更する。
     * 	変更するクラスは、このクラスを継承したサブクラスで指定する。
     */
    public function _decorate(PDOStatement $stmt) {
        $stmt->setFetchMode(PDO::FETCH_CLASS, get_class($this));
        return $stmt;
    }

    /**
     * 	ModelAbstract::find($arg=0)
     *
     * 	@param $arg 以下のいずれかを選択し、検索条件を指定する
     * 	・first、または引数なし
     * 		昇順でレコード1件のオブジェクトを取得する
     * 		ただし、主キーが複合主キーの場合や、オートインクリメントでない場合は正しい動作を保証しない。
     *
     * 	・last
     * 		降順でレコード1件のオブジェクトを取得する
     * 		ただし、主キーが複合主キーの場合や、オートインクリメントでない場合は正しい動作を保証しない。
     *
     * 	・all
     * 		全件検索し、レコード全てのオブジェクトを配列で取得する
     *
     * 	・数字
     * 		主キーの値を指定し、レコード1件のオブジェクトを取得する
     * 		ただし、主キーが複合主キーの場合や、オートインクリメントでない場合は正しい動作を保証しない。
     *
     * 	・配列
     * 		引数が配列の場合、詳細条件検索としてfindByConditionsメソッドがコールされる。
     * 		WHERE句、GROUP BY&HAVING句、ORDER BY句、LIMIT句に対応している。
     * 		詳しくはfindByConditionsメソッドの解説を閲覧のこと。
     *
     * 	・例外 InvalidArgumentException
     * 		上記以外の引数が指定された場合スローされる。
     *
     * 	@return 複数件数の場合はこのクラスのオブジェクトの配列、1件の場合はオブジェクトが返される。
     */
    public function find($arg = 0) {
        if (is_null($this->_db)) {
            static::setUp();
        }
        // 引数が配列の場合、詳細条件検索を行いSELECTした結果を返す
        if (is_array($arg)) {
            return $this->findByConditions($arg);
        }
        // 引数なし、または'first'が指定された場合は昇順で1件のオブジェクトが返される
        if ($arg === 0 || $arg === 'first') {
            return $this->findFirst();

            // 引数に'last'が指定された場合は降順で1件のオブジェクトが返される
        } elseif ($arg === 'last') {
            return $this->findLast();

            // 引数に'all'が指定された場合は全件をSELECTした結果を返す
        } elseif ($arg === 'all') {
            return $this->findAll();

            // それ以外の場合で引数が数字ならば主キーとしてSELECTした結果を返す
        } elseif (is_numeric($arg)) {
            return $this->findPrimary($arg);

            // いずれでもない場合はInvalidArgumentExceptionを発生させる
        } else {
            throw new InvalidArgumentException;
        }
    }

    private function findFirst() {
        $query = sprintf('SELECT * FROM %s ORDER BY %s ASC', $this->_tableName, $this->_primary);
        $stmt = $this->_db->prepare($query);
        $stmt->bindParam(':id', $primary, PDO::PARAM_INT);
        $stmt->execute();

        $stmt = $this->_decorate($stmt);
        return $stmt->fetch();
    }

    private function findLast() {
        $query = sprintf('SELECT * FROM %s ORDER BY %s DESC', $this->_tableName, $this->_primary);
        $stmt = $this->_db->prepare($query);
        $stmt->bindParam(':id', $primary, PDO::PARAM_INT);
        $stmt->execute();

        $stmt = $this->_decorate($stmt);
        return $stmt->fetch();
    }

    private function findPrimary($primary) {
        $query = sprintf('SELECT * FROM %s WHERE %s = :id', $this->_tableName, $this->_primary);
        $stmt = $this->_db->prepare($query);
        $stmt->bindParam(':id', $primary, PDO::PARAM_INT);
        $stmt->execute();

        $stmt = $this->_decorate($stmt);
        return $stmt->fetch();
    }

    private function findAll() {
        $query = sprintf('SELECT * FROM %s', $this->_tableName);
        $stmt = $this->_decorate($this->_db->query($query));
        return $stmt->fetchAll();
    }

    /**
     * findByConditions(array $conditions)
     *
     * 条件を連想配列にして渡すことで結果セットを返す。
     * 	連想配列のキーは指定された書式にする必要がある。
     *
     * $conditions['where']：WHERE句を配列で渡す
     * $conditions['groupby']：GROUP BY句を配列、および文字列で渡す
     * $conditions['having']：HAVING句を配列で渡す
     * $conditions['orderby']：ORDER BY句を配列、および文字列で渡す
     * $conditions['limit']：LIMIT句を文字列で渡す
     *
     * 	@param $conditions 条件の連想配列
     * @return 条件でSELECTを行った結果セット
     */
    public function findByConditions(array $conditions) {
        $query = sprintf('SELECT * FROM %s', $this->_tableName);

        // $conditionsにwhereをキーとした連想配列があればWHERE文を追加する
        if (array_key_exists('where', $conditions)) {
            $query .= $this->addWhere($conditions['where']);
        }

        // $conditionsにgroupbyをキーとした連想配列があればGROUP BY句を追加する
        if (array_key_exists('groupby', $conditions)) {
            $query .= $this->addGroupBy($conditions['groupby']);

            // $conditionsにhavingをキーとした連想配列があればHAVING句を追加する
            if (array_key_exists('having', $conditions)) {
                $query .= $this->addHaving($conditions['having']);
            }
        }

        // $conditionsにorderbyをキーとした連想配列があればORDER BY句を追加する
        if (array_key_exists('orderby', $conditions)) {
            $query .= $this->addOrderBy($conditions['orderby']);
        }

        // $conditionsにlimitをキーとした連想配列があればLIMIT句を追加する
        if (array_key_exists('limit', $conditions)) {
            $query .= $this->addLimit($conditions['limit']);
        }

//        echo $query;
        $stmt = $this->_decorate($this->_db->query($query));
        return $stmt->fetchAll();
    }

    /**
     * 	addWhere($where)
     *
     * 渡された条件からWHERE句を返す
     *
     * 渡された引数が一次元の連想配列の場合、全てをAND条件としてWHERE句を形成する。
     * $where = array('price' => '300', 'date' => '2013-12-03');
     *
     * 	条件に「=」以外の指定子を使いたい場合は、半角スペースで区切ってkey内に設定する
     * $where = array('price >' => '300', 'date' => '2013-12-03');
     *
     *
     * さらに細かい条件を付けたい場合は以下の書式を使う。
     *
     * 	・条件文にANDやORを指定したい場合
     * 		ANDの場合：
     * 		例）$where['and'] = array('price >' => '300', 'date' => '2013-12-03');
     * 	
     * 		ORの場合：
     * 		例）$where['or'] = array('price >' => '300', 'date' => '2013-12-03');
     *
     *
     * 	・条件文に（）を使う場合
     * 	$where['bracket']には（）でくくる場所を配列にして渡す
     * 		二つ以上のカッコには現在未対応。
     *
     * 		例）$where['bracket'] = array( 'price >' => '300', 'date' => '2013-12-03');
     *
     *
     * @param $where WHERE句の条件の配列
     * @return WHERE句のクエリ（文字列）
     */
    private function addWhere($where) {
        $query = '';

        // $conditions['where']が配列になっているか判定し、なっていなければInvalidArgumentExceptionをスローする
        if (is_array($where)) {
            // $whereのキーに'bracket'があった場合は（）つきの条件句を生成するため再帰処理を行う
            if (array_key_exists('bracket', $where)) {
                $query .= '(' . $this->addWhere($where['bracket']) . ')';

                // $whereのキーにandやorがなければ全てをAND条件としてWHERE句を作成
            } elseif (!( array_key_exists('and', $where) || array_key_exists('or', $where) )) {
                $query .= $this->addWhereAnd($where);

                // あった場合、それぞれの関数を呼び出す
            } else {
                if (array_key_exists('or', $where)) {
                    var_dump($where);
                    $query .= $this->addWhereOr($where['or']);
                }
                if (array_key_exists('and', $where)) {
                    $query .= $this->addWhereAnd($where['and']);
                }
            }
        } else {
            $query = $where;
        }

        // 先頭にWHEREをつけたして戻り値として返す
        $query = sprintf(' WHERE %s', $query);
        return $query;
    }

    /**
     * addWhereAnd(array $where)
     *
     * 渡された値からAND条件のWHERE句を返す
     *
     * @param $where AND条件の配列
     * @return $query AND条件のクエリ文字列
     */
    private function addWhereAnd(array $where) {
        // WHEREの条件を取り出し、WHERE文を追加
        $query = '';
        $db = $this->_db;

        if (in_array('or', $where) && in_array('and', $where)) {
            if (in_array('or', $where)) {
                $query .= $this->addWhereOr($where['or']);
            } elseif (in_array('and', $where)) {
                $query .= $this->addWhereAnd($where['and']);
            }

            $query .= ' AND ';

            if (in_array('or', $where)) {
                $query .= $this->addWhereOr($where['or']);
            } elseif (in_array('and', $where)) {
                $query .= $this->addWhereAnd($where['and']);
            }
        } else {
            if (in_array('or', $where)) {
                $query .= $this->addWhereOr($where['or']);
            } elseif (in_array('and', $where)) {
                $query .= $this->addWhereAnd($where['and']);
            }
        }

        // 引数が連想配列と配列とで処理を分ける
        if ($this->isVector($where)) {
            foreach ($where as $value) {
                // $queryが空でなければANDで区切る
                if ($query !== '') {
                    $query .= ' AND ';
                }

                $query .= $this->_db->quote($value);
            }
        } else {
            foreach ($where as $key => $value) {
                // $queryが空でなければANDで区切る
                if ($query !== '') {
                    $query .= ' AND ';
                }

                // $keyが空白で区切られていたら比較演算子の指定あり、区切られていなければ指定なしとして分岐
                if (preg_match('/^\S+\s\S+$/', $key)) {
                    $keys = preg_split('/\s/', $key);
                    if (is_array($value) && array_key_exists('func', $value)) {
                        $query .= $keys[0] . ' ' . $keys[1] . '' .$value['func']['name'] . '(' . $value['func']['arg'] . ')';
                    }else {
                        $query .= $keys[0] . ' ' . $keys[1] . ' ' . $this->_db->quote($value);
                    }
                } else {
                    if (is_array($value) && array_key_exists('func', $value)) {
                        $query .= $key. ' = ' . $value['func']['name'] . '(' . $value['func']['arg'] . ')';
                    }
                    $query .= $key . ' = ' . $this->_db->quote($value);
                }
            }
        }
        return $query;
    }

    /**
     * addWhereOr($where)
     *
     * 渡された値からOR条件のWHERE句を返す
     *
     * @param $where OR条件の配列
     * @return $query OR条件のクエリ文字列
     */
    private function addWhereOr(array $where) {
        // WHEREの条件を取り出し、WHERE文を追加
        $query = '';
        if (in_array('or', $where) && in_array('and', $where)) {
            if (in_array('or', $where)) {
                $query .= $this->addWhereOr($where['or']);
            } elseif (in_array('and', $where)) {
                $query .= $this->addWhereAnd($where['and']);
            }

            $query .= ' OR ';

            if (in_array('or', $where)) {
                $query .= $this->addWhereOr($where['or']);
            } elseif (in_array('and', $where)) {
                $query .= $this->addWhereAnd($where['and']);
            }
        } else {
            if (in_array('or', $where)) {
                $query .= $this->addWhereOr($where['or']);
            } elseif (in_array('and', $where)) {
                $query .= $this->addWhereAnd($where['and']);
            }
        }

        // 引数が連想配列と配列とで処理を分ける
        if ($this->isVector($where)) {
            foreach ($where as $value) {
                // $queryが空でなければANDで区切る
                if ($query !== '') {
                    $query .= ' OR ';
                }

                $query .= $this->_db->quote($value);
            }
        } else {
            foreach ($where as $key => $value) {
                // $queryが空でなければORで区切る
                if ($query !== '') {
                    $query .= ' OR ';
                }

                // $keyが空白で区切られていたら比較演算子の指定あり、区切られていなければ指定なしとして分岐
                if (preg_match('/^\S+\s\S+$/', $key)) {
                    $keys = preg_split('/\s/', $key);
                    $query .= $keys[0] . ' ' . $keys[1] . ' ' . $this->_db->quote($value);
                } else {
                    $query .= $key . ' = ' . $this->_db->quote($value);
                }
            }
        }
        return $query;
    }

    /**
     * addGroupBy($group)
     *
     * 渡された値からGROUP BY句を返す
     *
     * @param $group GROUP BY句の条件の配列
     * @return $query GROUP BY句のクエリ（文字列）
     */
    private static function addGroupBy($group) {
        $query = '';
        foreach ($group as $value) {
            // $queryが空でなければ,で区切る
            if ($query !== '') {
                $query .= ',';
            }

            $query .= ' ' . $this->_db->quote($value);
        }

        $query = sprintf(' GROUP BY %s', $query);
        return $query;
    }

    /**
     * addHaving($having)
     *
     * 	渡された値から、HAVING句を返す
     *
     * @param $having HAVING句の条件の配列
     * @return $query HAVING句部分のクエリ（文字列）
     */
    private function addHaving($having) {
        $query = $this->addWhere($having);
        $query = str_replace('WHERE', 'HAVING', $query);
        return $query;
    }

    /**
     * addOrderBy($orderby)
     *
     * 	引き渡された値から、ORDER BY句を返す
     *
     * 	@param $orderby ORDER BY句の条件の変数、または配列
     * @return $qyery ORDER BY句部分のクエリ（文字列）
     */
    private function addOrderBy($orderby) {
        $query = '';
        if (!is_array($orderby)) {
            $orderby = array($orderby);
        }

        // $orderbyが連想配列でなければ全ての条件句をASCとする
        if ($this->isVector($orderby)) {
            foreach ($orderby as $value) {
                if ($query !== '') {
                    $query .= ', ';
                }

                $query .= $this->_db->quote($value) . ' ASC';
            }
            // $orderbyが連想配列であった場合、valueをASC/DESC、keyをフィールド名として条件句を作る
        } else {
            foreach ($orderby as $key => $value) {
                if ($query !== '') {
                    $query .= ', ';
                }
                if ($value === '' || $value === 'ask') {
                    $query .= $key . ' ASC';
                } else {
                    $query .= $key . ' DESC';
                }
            }
        }

        // ORDER BY句を追加して戻り値として返す
        $query = sprintf(' ORDER BY %s', $query);
        return $query;
    }

    /**
     * 	private static addLimit($limit)
     *
     * 引数として渡された値からLIMIT句のクエリ（文字列）を返す
     *
     * @param $limit LIMIT句の条件の文字列
     * @return $query LIMIT句部分のクエリ（文字列）
     */
    private function addLimit($limit) {
        if (count($limit) === 1) {
            $query = ' LIMIT ' . $limit;
        } else {
            $query = ' LIMIT ' . $limit[0] . ',' . $limit[1];
        }

        return $query;
    }

    public function rowCount(array $conditions = array()) {
        $query = sprintf('SELECT COUNT(*) FROM %s', $this->_tableName);
        if (!empty($conditions)) {
            // $conditionsにwhereをキーとした連想配列があればWHERE文を追加する
            if (array_key_exists('where', $conditions)) {
                $query .= $this->addWhere($conditions['where']);
            }

            // $conditionsにgroupbyをキーとした連想配列があればGROUP BY句を追加する
            if (array_key_exists('groupby', $conditions)) {
                $query .= $this->addGroupBy($conditions['groupby']);

                // $conditionsにhavingをキーとした連想配列があればHAVING句を追加する
                if (array_key_exists('having', $conditions)) {
                    $query .= $this->addHaving($conditions['having']);
                }
            }

            // $conditionsにorderbyをキーとした連想配列があればORDER BY句を追加する
            if (array_key_exists('orderby', $conditions)) {
                $query .= $this->addOrderBy($conditions['orderby']);
            }

            // $conditionsにlimitをキーとした連想配列があればLIMIT句を追加する
            if (array_key_exists('limit', $conditions)) {
                $query .= $this->addLimit($conditions['limit']);
            }
        }
        $stmt = $this->_db->query($query);
        $result = $stmt->fetch();
        return $result['count(*)'];
    }

    /**
     * 	public static query($query)
     * 	PDO::queryとほぼ同等の機能を有する。
     *
     * 	@param SQL文
     * 	@return PDOStatement SQL文をデータベースに渡して得られる結果セットのPDOStatementオブジェクトを返す
     */
    public function query($query) {
        static::setUp();
//        echo $query;
        return $this->_decorate($this->_db->query($query))->fetchAll();
    }

    /**
     * protected static setSchema()
     *
     * 	static変数である$_schemaに、テーブルの持つフィールド名とデータ型をセットするメソッド。
     * 	現在はmysqlにのみ対応している。
     */
    protected function setSchema() {
        $result = $this->_db->query('describe ' . $this->_tableName);
        foreach ($result as $row) {
            if (strpos($row['type'], 'int') !== false) {
                $type = static::INTEGER;
            } elseif (strpos($row['type'], 'char')) {
                $type = static::STRING;
            } elseif (strpos($row['type'], 'date')) {
                $type = static::STRING;
            } elseif (strpos($row['type'], 'double')) {
                $type = static::DOUBLE;
            } elseif (strpos($row['type'], 'float')) {
                $type = static::FLOAT;
            } elseif (strpos($row['type'], 'bool')) {
                $type = static::BOOLEAN;
            }
            static::$_schema[strtolower($row['field'])] = $type;
        }
    }

    /**
     * protected static isVector(array $arr)
     * 渡された配列が通常の配列か否かを判定する。
     * 	配列ならばtrue、連想配列ならfalseを返す。
     *
     * @param $arr 判定したい配列
     * @return 配列ならばtrue、連想配列ならfalse
     */
    protected function isVector(array $arr) {
        return array_values($arr) === $arr;
    }

    /**
     * 	protected static ModelAbstract::setUp()
     *
     */
    protected function setUp() {
        $this->_db = DbManager::getInstance()->getConnection();

        if (empty($this->_tableName)) {
            $this->_tableName = str_replace('Model', '', get_class($this));
        }
        $this->setSchema();
    }

}
