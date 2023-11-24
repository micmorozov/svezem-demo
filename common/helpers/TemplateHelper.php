<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 16.11.17
 * Time: 12:30
 */

namespace common\helpers;

use common\helpers\twig_extensions\GeoRussianDecline;
use common\helpers\twig_extensions\NameRussianDecline;
use common\helpers\twig_extensions\PluralizeExtension;
use common\helpers\twig_extensions\RussianDeclineBase;
use common\models\CargoCategory;
use common\models\City;
use common\models\Country;
use common\models\FastCity;
use common\models\LocationInterface;
use common\models\PageTemplates;
use common\models\PageTemplateType;
use common\models\Region;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\SyntaxError;
use Twig\Loader\FilesystemLoader;
use yii\caching\TagDependency;

class TemplateHelper
{
    const geoFields = ['title', 'desc', 'h1', 'keywords', 'text', 'tag_name', 'cargoFormTitle', 'cargo_hint'];
    const nameFields = ['tr_title', 'tk_title', 'cargo_title'];

    /**
     * @param $pageType
     * @param LocationInterface $location
     * @param CargoCategory $category
     * @param array $params
     * @return bool|PageTemplates|null
     * @throws LoaderError
     * @throws SyntaxError
     */
    static public function get($pageType, LocationInterface $location = null, CargoCategory $category = null, array $params = [])
    {
        if( !$tpl = self::findTemplate($pageType, $location, $category)) {
            return false;
        }

        //определяем шаблон
        if($location instanceof City) {
            //Заменяем спецтеги в шаблоне
            $params['city'] = $params['city_from'] = $location->getTitle();

            $params['country'] = $location->country->getTitle();

            $params['city_region_ex'] = $params['city_from_region_ex'] = $location->getTitleWithRegionForTwig();
        } elseif($location instanceof Region) {
            $params['region'] = $location->getTitle();
            $params['country'] = $location->country->getTitle();
        } elseif($location instanceof Country){
            $params['country'] = $location->getTitle();
        }

        if($category) {
            $params['category'] = $category->category;
            $params['category_rod'] = $category->category_rod;
        }

        return self::fillTemplate($tpl, $params);
    }

    /**
     * @param string $pageType
     * @param LocationInterface $location
     * @param null|integer $category_id
     * @param bool $findDefault
     * @return PageTemplates|null
     */
    static public function findTemplate(string $pageType, LocationInterface $location = null, CargoCategory $category = null, $findDefault = true)
    {
        $categoryId = $category ? $category->id : null;

        $templateType = PageTemplateType::COUNTRY;
        if($location instanceof City){
            $templateType = PageTemplateType::CITY;
        }
        elseif($location instanceof Region){
            $templateType = PageTemplateType::REGION;
        }
        elseif($location instanceof Country){
            $templateType = PageTemplateType::COUNTRY;
        }

        $tagDependency = new TagDependency(['tags' => [
            'findTemplate',
            $pageType,
            $templateType,
            $location,
            $category
        ]]);

        // 1. Ищем шаблон соответствующий переденным параметрам
        $query = PageTemplates::find()
            ->where(['and',
                ['type' => $pageType],    // Тип шаблона
                ['is_city' => $templateType], // Город, Регион или Страна
                ['category_id' => $categoryId] // Категория
            ])
            ->cache(3600, $tagDependency);

        //если задан ИД города, региона или страны
        if($location){
            $query->andWhere(['city_id' => $location->getId()]); // ИД города, региона или Страны
        }

        $tpl = $query->one();

        //если найден шаблон или не требуется поиск шаблона по умолчанию
        if($tpl || !$findDefault)
            return $tpl;

        // 2. Если укзан город (но не был найден на предыдущем шаге) и требуется по умолчанию, ищем без указания города
        if($location){
            $tpl = PageTemplates::find()
                ->where(['and',
                    ['type' => $pageType],
                    ['is_city' => $templateType],
                    ['city_id' => null],
                    ['category_id' => $categoryId]
                ])
                ->cache(3600, $tagDependency)
                ->one();

            if($tpl)
                return $tpl;
        }

        // 3. Если указана категория, но шаблон не нашли - ищем шаблон для родительской категории
        if($category && $categoryParents = $category->parents){
            $parentCaregory = array_shift($categoryParents);

            if($parentCaregory) {
                $tpl = self::findTemplate($pageType, $location, $parentCaregory, $findDefault);

                if ($tpl) {
                    return $tpl;
                }
            }
        }

        // 4. Ищем шаблон по умолчанию
        return PageTemplates::find()
            ->where(['and',
                ['type' => $pageType],
                ['is_city' => $templateType],
                ['city_id' => null],
                ['category_id' => null]
            ])
            ->cache(3600, $tagDependency)
            ->one();
    }

    /**
     * @param $tpl
     * @param $params
     * @param array $attributes
     * @return PageTemplates|null
     * @throws LoaderError
     * @throws SyntaxError
     */
    static public function fillTemplate($tpl, $params, $attributes = ['title', 'desc', 'h1', 'keywords', 'text', 'tag_name', 'cargoFormTitle', 'cargo_hint'])
    {
        $tpl = clone $tpl;

        $loader = new FilesystemLoader('');
        $geoTwig = new Environment($loader);
        $geoTwig->setExtensions([
            new GeoRussianDecline(),
            new PluralizeExtension()
        ]);

        $nameTwig = new Environment($loader);
        $nameTwig->setExtensions([
            new NameRussianDecline(),
            new PluralizeExtension()
        ]);

        foreach($attributes as $attr){
            $string = $tpl->$attr ?? '';

            //В зависимости от поля используются разные
            //расширения для падежей
            if(in_array($attr, self::geoFields)){
                $twig = $geoTwig;
            } elseif(in_array($attr, self::nameFields)){
                $twig = $nameTwig;
            }

            if( isset($twig) ){
                $template = $twig->createTemplate($string);
                $tpl->$attr = $template->render($params);
            }
        }

        return $tpl;
    }
}