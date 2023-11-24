<?php

namespace frontend\controllers;

use common\behaviors\NoSubdomain;
use common\helpers\LocationHelper;
use frontend\actions\CityListAction;
use frontend\actions\CitySearchListAction;
use frontend\actions\LocationDropDownListAction;
use frontend\components\CityComponent;
use Svezem\Services\MatrixContentService\MatrixContentService;
use Yii;
use yii\filters\ContentNegotiator;
use yii\filters\PageCache;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class CityController extends Controller
{
    /** @var MatrixContentService  */
    private $matrixContentService;

    public function __construct($id, $module, MatrixContentService $matrixContentService, $config = [])
    {
        $this->matrixContentService = $matrixContentService;

        parent::__construct($id, $module, $config);
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            [
                'class' => ContentNegotiator::class,
                'only' => ['list', 'search-list', 'locationdd-list'],
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
                ]
            ],

            [
                'class' => NoSubdomain::class,
                'only' => ['index']
            ],

            [
                'class' => PageCache::class,
                'only' => ['index'],
                'duration' => 86400,
                'variations' => [
                    Yii::$app->request->get('char')
                ]
            ],
        ];
    }

    public function actions()
    {
        return [
            'list' => CityListAction::class,
            'search-list' => CitySearchListAction::class,
            'locationdd-list' => LocationDropDownListAction::class
        ];
    }

    /**
     * Отображаем список регионов с городами для выбора
     * @param null $char Для быстрого поиска города
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionIndex($char = null)
    {
        $alphabet = CityComponent::getAlphabet();

        //Переход по букве, которой нет в алфавите
        if( $char && !in_array($char, $alphabet)){
            throw new NotFoundHttpException('Страница не найдена');
        }

        return $this->render('index', [
            'char' => $char,
            'alphabet' => $alphabet,
            'greatRequestCity' => !$char ? CityComponent::greatRequestCity() : null,
            'regions' => $char ? CityComponent::getRegionCity($char) : null,
            'matrixContentService' => $this->matrixContentService
        ]);
    }
}