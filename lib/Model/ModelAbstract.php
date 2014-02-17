<?php

/* * ***************************************************************************************************
 * 	DataModelAbstract
 *
 * 	テーブルから取得した行をオブジェクトとしてモデル化するためのクラス（ドメインクラス）。
 * 	このクラスは抽象クラスであり、扱うテーブルにあわせてオーバーライドして実装すること。
 * 	
 * 	PDOのフェッチモードをPDO::FETCH_CLASSにし、結果セットをこのクラスのインスタンスとして取得することで、
 * 	レコードごとに1つのオブジェクトとして扱うことが出来る。
 * 	
 * 	@author 植森康友
 * 	@lastupdate 2013/11/21 15:33
 *
 * 	参考サイト：http://blog.tojiru.net/article/277021312.html
 * **************************************************************************************************** */
abstract class ModelAbstract extends FindModelAbstract {

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

    /**
     * 	toArray()
     *
     * フィールドを配列として取得する
     */
    public function toArray() {
        return $this->_data;
    }

    /**
     * 	fromArray(array $arr)
     *
     * 	フィールドを配列としてセットする
     *
     * 	@param $arr フィールドの配列
     */
    public function fromArray(array $arr) {
        foreach ($arr as $key => $val) {
            $this->__set($key, $val);
        }
    }

    /**
     * 	save()
     *
     * 	このオブジェクトのフィールドに設定されたデータを基に更新、あるいは挿入処理を行う。
     * 	このオブジェクトの主キーのフィールドに値が設定されていれば更新、
     * 	値が設定されていなければ挿入処理を自動的に振り分ける。
     *
     * 	@return ステートメントの実行結果。成功ならTRUE、失敗ならFALSE
     */
    public function save() {
        $primary = $this->_primary;
        if (!empty($this->_data->$primary)) {
            $this->update();
        } else {
            $this->insert();
        }
    }

    /**
     * 	insert()
     *
     * 	挿入処理を行う。主キーにAUTO INCREMENTが設定されている必要があるため注意すること。
     * 	この処理はプリペアドステートメントを用いたSQLインジェクション対策が行われている。
     * 	
     * @return ステートメントの実行結果。成功ならTRUE、失敗ならFALSE
     */
    protected function insert() {
        // 挿入データをオブジェクトのフィールドから取得
        $data = $this->_data;

        // 配列のキーから挿入項目とプレースホルダを文字列として作成する
        $arrayKeys = implode(',', array_keys($data));
        $placeHolders = ':' . implode(', :', array_keys($data));

        // クエリ文を作成
        $query = sprintf('INSERT INTO %s(%s) VALUES(%s)', $this->_tableName, $arrayKeys, $placeHolders);

        // クエリ文をステートメントに準備
        $stmt = $this->_db->prepare($query);

        // 値をバインド処理
        foreach ($data as $key => $row) {
            $stmt->bindValue(':' . $key, $row);
        }

        // ステートメントを実行
        try {
            return $stmt->execute();
        } catch (Exception $e) {
            'エラー：{$e->getMessage()}';
        }
    }

    /**
     * 	protected update()
     *
     * 	更新処理を行う。主キーが数字で管理されている前提であるため注意すること。
     * 	この更新処理はプリペアドステートメントを用いたSQLインジェクション対策が行われている。
     *
     * 	@return このステートメントの実行結果。成功ならTRUE、失敗ならFALSE。
     */
    protected function update() {
        // 更新データをオブジェクトのフィールドから取得
        $data = $this->_data;

        // 主キーを設定
        $primary = $this->_primary;

        // フィールド名を配列から取得
        $arrayKeys = array_keys($data);

        // テーブル名からSETの条件文を作成
        foreach ($arrayKeys as $key) {
            if (!($key === $primary)) {
                if (!isset($set)) {
                    $set = $key;
                    $set .= ' = :' . $key;
                } else {
                    $set .= ', ' . $key;
                    $set .= ' = :' . $key;
                }
            }
        }

        // 条件文として主キーを用いて作成
        $where = $primary . ' = ' . $data[$primary];

        // クエリ文を作成
        $query = sprintf('UPDATE %s SET %s WHERE %s', $this->_tableName, $set, $where);

        // クエリ文をステートメントを準備する
        $stmt = $this->_db->prepare($query);

        // プレースホルダに各種条件をパラメータとしてバインドを行う。
        // switch文で型の確認を行い、各型に応じたデータ型でバインドする。
        foreach ($data as $key => $row) {
            if (!($key === $primary)) {
                switch(static::$_schema[$key]) {
                    case static::INTEGER:
                        $stmt->bindValue(':' . $key, $row, PDO::PARAM_INT);
                        break;
                    case static::STRING:
                        $stmt->bindValue(':' . $key, $row, PDO::PARAM_STR);
                        break;
                    case static::BOOLEAN:
                        $stmt->bindValue(':' . $key, $row, PDO::PARAM_BOOL);
                        break;
                    default:
                        $stmt->bindValue(':' . $key, $row);
                }
            }
        }

        // ステートメントを実行する
        try {
            return $stmt->execute();
        } catch (Exception $e) {
            'エラー：{$e->getMessage()}';
        }
    }

    /**
     * 	delete()
     *
     * 	このオブジェクトの指すレコードをデータベースから削除する
     *
     * 	@return ステートメントの実行結果。成功ならTRUE、失敗ならFALSEが返る。
     */
    public function delete() {
        $data = $this->_data;
        $primary = $this->_primary;
        $where = $this->_primary . ' = ' . $data[$primary];
        $query = sprintf('DELETE FROM %s WHERE %s', $this->_tableName, $where);
        $stmt = $this->_db->prepare($query);

        try {
            return $stmt->execute();
        } catch (Exception $e) {
            'エラー：{$e->getMessage()}';
        }
    }

    /**
     * 	isValid()
     *
     * 	セットされている値が仕様の範囲かどうかをチェックする。
     * 	抽象メソッドであるため、サブクラスでオーバーライドする必要がある。
     *
     * 	return 仕様の範囲ならture、範囲外ならfalse
     */
//	public function isValid();
}
