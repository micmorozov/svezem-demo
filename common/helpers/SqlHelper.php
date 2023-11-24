<?php


namespace common\helpers;


use Yii;

class SqlHelper
{
    /**
     * Строит sql запрос с переданными параметрами
     *
     * @param $tableName - Имя таблицы
     * @param array $attrs - Наименование всех полей в таблице
     * @param array $vals - Значения полей
     * @param bool $ignore - INSERT IGNORE
     * @return string
     */
    static public function buildBatchInsertQuery($tableName, $attrs=[], $vals=[], $ignore=false):string
    {
        $sql = Yii::$app->db
            ->createCommand()
            ->batchInsert($tableName, $attrs, $vals)
            ->rawSql;

        if($ignore){
            $sql = str_replace('INSERT INTO', 'INSERT IGNORE INTO', $sql);
        }

        return $sql;
    }
}