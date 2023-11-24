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

class IntercityUrlRule extends UrlRule
{
    public function createUrl($manager, $route, $params)
    {
        if(isset($params['root']) && $params['root'] instanceof CargoCategory) {
            $params['root'] = $params['root']->slug;
        }

        // Проверяем что city не null
        if(isset($params['cityFrom']) && $params['cityFrom'] instanceof LocationInterface) {
            $params['cityFrom'] = $params['cityFrom']->getCode();
        }

        // Проверяем что city не null
        if(isset($params['cityTo']) && $params['cityTo'] instanceof LocationInterface) {
            $params['cityTo'] = $params['cityTo']->getCode();
        }

        return parent::createUrl($manager, $route, $params);
    }

    public function parseRequest($manager, $request)
    {
        $params = [];

        // Нормализация урла через родительский метод
        $reqParams = parent::parseRequest($manager, $request);
        if(!$reqParams || !$reqParams = $reqParams[1]) return false;

        if(isset($reqParams['cityFrom'])) {
            $params['cityFrom'] = City::find()->where(['code' => $reqParams['cityFrom']])
                ->cache(86400, new TagDependency(['tags' => "City:{$reqParams['cityFrom']}"]))
                ->one();
            if (!$params['cityFrom'])
                return false;
        }

        if(isset($reqParams['cityTo'])) {
            $params['cityTo'] = City::find()->where(['code' => $reqParams['cityTo']])
                ->cache(86400, new TagDependency(['tags' => "City:{$reqParams['cityTo']}"]))
                ->one();
            if (!$params['cityTo'])
                return false;
        }

        if(isset($reqParams['root'])) {
            $params['root'] = CargoCategory::find()
                ->where([
                    'slug' => $reqParams['root'],
                    'root' => true
                ])
                ->cache(86400, new TagDependency(['tags' => "CargoCategory"]))
                ->one();

            if (!$params['root']) {
                return false;
            }
        }

        return [$this->route, $params];
    }
}