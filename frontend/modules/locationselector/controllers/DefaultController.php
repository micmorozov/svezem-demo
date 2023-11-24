<?php

namespace frontend\modules\locationselector\controllers;

use common\helpers\Utils;
use common\models\CargoCategory;
use common\models\City;
use yii\db\Expression;
use yii\filters\ContentNegotiator;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\Response;
use Svezem\Services\MatrixContentService\MatrixContentService;

class DefaultController extends \yii\web\Controller
{
    private $matrixContentService;

    public function __construct($id, $module, MatrixContentService $matrixContentService, $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->matrixContentService = $matrixContentService;
    }

    public function behaviors()
    {
        return [
            [
                'class' => ContentNegotiator::class,
                'only' => ['categories'],
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
                ]
            ],
        ];
    }

    public function actionCategories($location = null)
    {
        $location = City::findOne(['id' => $location]);
        if (! $location) {
            return [];
        }
        $root_cat = ['gruzoperevozki', 'pereezd', 'arenda-avto', 'arenda-spectehniki', 'vyvoz'];
        $rootCategories = CargoCategory::find()
            ->where(['slug' => $root_cat])
            ->orderBy(new Expression('FIELD(slug, "' . implode('","', $root_cat) . '")'))
            ->all();
        $result = [];
        foreach ($rootCategories as $category) {
            $categoryRequired = Utils::check_mask($location->size, $category->city_size_mask);
            $isEnoughtContent = $categoryRequired || $this->matrixContentService->isEnoughContentAnyDirection('cargo-transportation-view', $location, $category);
            if($isEnoughtContent) {
                $categoryUrl = Url::toRoute([
                    "/cargo/transportation/search2",
                    'slug' => $category,
                    'location' => $location
                ]);
                //$result[] =  '<li>' . Html::a($category->category, $categoryUrl) . '</li>';
                $result[] =  [
                    'label' => $category->category,
                    'url' => $categoryUrl,
                ];
            }
        }
        return $result;
    }
}
