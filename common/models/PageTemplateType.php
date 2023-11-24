<?php


namespace common\models;


class PageTemplateType
{
    const CITY = 1;
    const COUNTRY = 0;
    const REGION = 2;

    /**
     * Список доступных типов шаблонов
     */
    public static function getTypeList():array
    {
        return [
            self::CITY => 'Города',
            self::REGION => 'Региона',
            self::COUNTRY => 'Страны'
        ];
    }

    /**
     * Возвращает наименование типа страницы по его коду
     * @param int $type
     * @return string
     */
    public static function getTypeName(int $type):string
    {
        $list = self::getTypeList();
        return $list[$type]??'';
    }
}