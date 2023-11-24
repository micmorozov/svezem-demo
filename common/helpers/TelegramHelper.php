<?php


namespace common\helpers;


class TelegramHelper
{
    /**
     * Город
     */
    const AREA_CITY = '10';

    /**
     * Регион
     */
    const AREA_REGION = '20';

    /**
     * Префикс для каналов
     */
    const CHAT_PREFIX = 'svezem_';

    const BOT_NAME = 'SvezemBot';

    /**
     * Строим наименование чата для Телеграма.
     * Чат строится так: Под $areaId отводится 8 знаков, спереди добавляется еще 2, указывающие на тип идентификатора
     *
     * @param $areaType - Тип идентификатора, 10 - город, 20 - регион
     * @param $areaId - Идентификатор области или города
     */
    private static function buildChatId($areaType, $areaId)
    {
        return self::CHAT_PREFIX . $areaType . sprintf("%'.08d", $areaId);
    }

    public static function getChatId($areaType, $areaId)
    {
        return '@' . self::buildChatId($areaType, $areaId);
    }

    /**
     * Получаем наименование чата куда публикуем все грузы
     * @return string
     */
    public static function getCommonChatId()
    {
        return '@svezem_allcargo';
    }

    public static function getLinkToCommonChannel()
    {
        return self::getDomain() . 'svezem_allcargo';
    }

    public static function getLinkToChannel($areaType, $areaId)
    {
        //return self::getDomain() . self::buildChatId($areaType, $areaId);
        // Используем переадресатор, что бы ссылка работала
        return self::getDomain() . self::buildChatId($areaType, $areaId);
    }

    /**
     * Ссылка на Телеграм бота SvezemBot
     * @return string
     */
    public static function getLinkToBot()
    {
        return self::getDomain() . self::BOT_NAME;
    }

    /**
     * Домен Телеграма, а то его блочат
     * @return string
     */
    private static function getDomain()
    {
        return 'https://t.me/';
    }
}