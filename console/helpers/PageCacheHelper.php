<?php
namespace console\helpers;

use micmorozov\yii2\gearman\Dispatcher;
use Yii;

class PageCacheHelper
{
    private static function dir_static_page(){
        return Yii::getAlias(Yii::$app->params['pageCacheDir']);
    }

    /**
     * Путь сохранения статического файла строится по URL
     * Каждая позиция отделенная слэшем "/" это директория. Значение последней позиции становится
     * наименованием html файла
     * Если URL на главную страницу, то путь строится как <домен>/index.html
     *
     * @param $url
     * @return bool|string
     */
    private static function getStaticFilePath($url)
    {
        $parsed = parse_url($url);

        if ( !isset($parsed['path']) || $parsed['path'] == '/') {
            $path = self::dir_static_page()."/{$parsed['host']}/index.html";
        } elseif (preg_match('/([\w+\/-]+)?\/([\w-]+)/u', $parsed['path'], $match)) {
            $path = self::dir_static_page()."/{$parsed['host']}{$match[1]}/{$match[2]}.html";
        } else {
            return false;
        }

        return $path;
    }

    /**
     * Добавляем урл в очередь на загрузку
     * @param string $url - URL адрес страницы
     * @param int $priority - Приоритет обработки
     */
    public static function fetchUrl($url, $priority = Dispatcher::LOW)
    {
        $url = trim($url);
        if (!$url) {
            return;
        }

        $pathToSave = self::getStaticFilePath($url);

        Yii::$app->gearman->getDispatcher()->background("fetchUrl", [
            'url' => $url,
            'pathToSave' => $pathToSave
        ], $priority);
    }
}