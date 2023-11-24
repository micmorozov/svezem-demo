<?php
namespace frontend\components\urlRules;

use common\helpers\CategoryHelper;
use common\helpers\LocationHelper;
use common\models\CargoCategory;
use common\models\City;
use common\models\Country;
use common\models\FastCity;
use common\models\LocationInterface;
use common\models\Region;
use frontend\modules\account\models\EmailForm;
use Yii;
use yii\base\BaseObject;
use yii\base\Exception;
use yii\caching\TagDependency;
use yii\web\NotFoundHttpException;
use yii\web\UrlRule;
use yii\web\UrlRuleInterface;

class CategoryUrlRule extends UrlRule
{
    public function createUrl($manager, $route, $params)
    {
        $slug = [];

        if(isset($params['location'])){
            if($params['location'] instanceof LocationInterface){
                $params['location'] = $params['location']->getCode();
            }

            $slug[] = $params['location'];
            unset($params['location']);
        }

        if(isset($params['slug'])) {
            if($params['slug'] instanceof CargoCategory){
                $params['slug'] = CategoryHelper::buildFullParentSlug($params['slug']);
            }

            if (is_array($params['slug'])) {
                if($params['slug'])
                    array_push($slug, ...CategoryHelper::categoryToLineSlug($params['slug']));
            } else {
                $slug[] = $params['slug'];
            }
        }

        $params['slug'] = implode('/', $slug);

        return parent::createUrl($manager, $route, $params);
    }

    public function parseRequest($manager, $request)
    {
        // Нормализация урла через родительский метод
        parent::parseRequest($manager, $request);

        $pathInfo = $request->getPathInfo();

        $matches = preg_split('%/%', $pathInfo);
        if(!$matches) return false;

        // Первым пунктом может быть город или регион
        // Надо проверить это
        $params['location'] = LocationHelper::getLocationByCode($matches[0]);

        // Если город или регион нашли удаляем элемент из массива
        if($params['location']) {
            array_shift($matches);
        }

        // Далее могут идти категории и подкатегории
        // Проверяем, что это так
        $categories = [];
        /** @var CargoCategory $parentCategory */
        $parentCategory = null;
        foreach($matches as $match){
            if(!$match) continue;

            // Проверяем, что все категории существуют
            $category = CargoCategory::find()
                ->where([
                    'slug' => $match
                ])
                ->cache(86400, new TagDependency(['tags' => "CargoCategory"]))
                ->one();

            if(!$category){
                return false;
            }

            // Если урл не соответствует настройке вложенности категорий
            if((!$parentCategory && $category->parentsids) ||
                ($parentCategory && !in_array($parentCategory->id, $category->parentsids))){
                return false;
            }

            $categories[] = $parentCategory = $category;
        }

        // Если нет категорий - выводим объявления по городу или региону
        if(!$categories)
            return ['/site/index', $params];

        $params['categories'] = $categories;

        return [$this->route, $params];
    }
}