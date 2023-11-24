<?php
/**
 * Класс по работе с UTM метками
 */

namespace common\helpers;

use yii\base\InvalidArgumentException;

class UTMHelper
{
    /**
     * Генерируем ссылку с UTM метками
     *
     * @param $url - ссылка к которой надо добавить UTM метки
     * Может быть как абсолютным адресом тат и относительным
     *
     * @param $utm_source - название рекламной площадки
     * Зачем нужен: Чтобы указать название источника трафика
     *
     * Примеры:
     * utm_source=google – контекстная реклама в Google Adwords
     * utm_source=yandex — контекстная реклама в Яндекс.Директ
     * utm_source=facebook — контекстная реклама в Facebook
     * utm_source=vk — контекстная реклама в Вконтакте
     *
     * @param $utm_medium - тип рекламы
     * В этом параметре рекомендуется использовать устоявшиеся значения, например:
     * cpc (cost per click) — это контекстная реклама,
     * display — баннерная реклама с оплатой за показы,
     * social_cpc — реклама в соцсетях с оплатой за клик
     *
     * Зачем нужен:
     * Чтобы определить тип кампании или рекламы
     *
     * Примеры:
     * utm_medium=organic – бесплатный переход
     * utm_medium=cpc – контекстная реклама (cost per click, плата за клик)
     * utm_medium=email — рассылка
     * utm_medium=social — социальные сети
     * utm_medium=banner — медийная реклама
     * utm_medium=cpa — другая реклама (cost per action, плата за действие)
     *
     * @param $utm_compaign - название кампании
     * Этот обязательный параметр можно задать произвольно
     *
     * Зачем нужен:
     * Позволит вам отличить одну рекламную кампанию от другой в статистике
     *
     * Примеры:
     * utm_campaign=mebel_dlya_doma – рекламная кампания мебели для дома
     *
     * @param string $utm_content - дополнительная информация, которую можно отслеживать, если совпадают другие параметры
     * Зачем нужен:
     * Часто используется как пометка для объявления внутри рекламной кампании. Название можно задать произвольно, удобнее
     * всего использовать важные характеристики объявления — подкатегория товара или услуги, тип самого объявления и т. п.
     *
     * Примеры:
     * utm_content=zero_block240×60 — баннер 240 на 60 про Zero блок на Тильде
     * utm_content=zero_block_text — текстовое объявление про Zero блок
     *
     * @param string $utm_term - ключевое слово, с которого начался показ объявления
     * Этот необязательный параметр можно задать произвольно
     *
     * Зачем нужен:
     * Позволит вам отличить одну рекламную кампанию от другой в статистике
     */
    static public function genUTMLink($url, $params): string
    {
        if(!isset($params['utm_source']) || !isset($params['utm_medium']) || !isset($params['utm_compaign']))
            throw new InvalidArgumentException('Требуются параметры: utm_source, utm_medium, utm_compaign');

        // Убираем из params пустые значения
        $params = array_filter($params, function($var) {
            return $var;
        });

        $url = parse_url($url);
        if (isset($url['query'])) {
            parse_str($url['query'], $output);
            $params = array_merge($output, $params);
        }

        $scheme = $url['scheme'] ? $url['scheme'] . "://" : '';
        $host = $url['host'] ?? '';

        return $scheme . $host . $url['path'] . "?" . http_build_query($params);

    }
}