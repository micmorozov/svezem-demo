<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 19.10.17
 * Time: 17:22
 */

namespace frontend\modules\tk\controllers;

use common\behaviors\NoSubdomain;
use common\models\City;
use common\models\FastCity;
use console\jobs\CalcDistance;
use console\jobs\jobData\CalcDistanceData;
use frontend\modules\tk\models\Tk;
use frontend\modules\tk\models\TkCompareSearch;
use micmorozov\yii2\gearman\Dispatcher;
use Yii;
use frontend\modules\tk\models\TkSearch;
use yii\filters\ContentNegotiator;
use yii\filters\VerbFilter;
use yii\helpers\Json;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use common\helpers\TemplateHelper;

class ComparisonController extends Controller
{
    public function behaviors()
    {
        return [
            NoSubdomain::class,

            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'search'  => ['post']
                ]
            ],
            [
                'class' => ContentNegotiator::class,
                'only' => ['search'],
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
                ]
            ],
        ];
    }

    public function actionIndex(){
        $model = new TkCompareSearch();
        $model->setScenario('PriceCompare');
        return $this->render('index', [
            'model' => $model,
            'pageTpl' => TemplateHelper::get('tk-comparison-view')
        ]);
    }

    public function actionSearch(){
        $model = new TkCompareSearch();
        $model->setScenario('PriceCompare');

        $total_tk = 0;

        $post = Yii::$app->request->post();
        if( Yii::$app->request->isAjax && $model->load($post) && $model->validate() ){
            $from_city = City::findOne($model->city_from);
            $to_city = City::findOne($model->city_to);

            if($from_city === null || $to_city === null) {
                throw new NotFoundHttpException();
            }

            //Job расчета растояния
            $calcJob = new CalcDistanceData();
            $calcJob->behavior = CalcDistance::BEHAVIOR_PUT_TO_SOCKET;
            $calcJob->city_from = $model->city_from;
            $calcJob->city_to = $model->city_to;
            $calcJob->socket_id = $post['socket_id'];

            Yii::$app->gearman->getDispatcher()->background($calcJob->getJobName(), $calcJob, Dispatcher::HIGH);

//            Yii::$app->gearman->getDispatcher()->background("CalcDistance", [
//                'behavoir' => CalcDistance::BEHAVIOR_PUT_TO_SOCKET,
//                'city_from' => $from_city->title_ru,
//                'city_to' => $to_city->title_ru,
//                'socket_id' => $post['socket_id']
//            ], Dispatcher::HIGH);

            $tks = Tk::find()->where(['status' => Tk::STATUS_ACTIVE])->asArray()->all();

            if($total_tk = count($tks)) {
                Yii::$app->redis->executeCommand('PUBLISH', [
                    'channel' => 'pubsub',
                    'message' => Json::encode([
                        'total_tk' => $total_tk,
                        'socket_id' => $post['socket_id']
                    ])
                ]);

                foreach($tks as $tk) {
                    Yii::$app->gearman->getDispatcher()->background("parseTkSite", [
                        'tk' => $tk,

                        'from_city_id' => $from_city->id,
                        'to_city_id' => $to_city->id,

                        'from_city_name' => $from_city->title_ru,
                        'to_city_name' => $to_city->title_ru,

                        'weight' => $model->weight,

                        'width' => $model->width,
                        'height' => $model->height,
                        'depth' => $model->depth,

                        'socket_id' => $post['socket_id'],
                        'session_timestamp' => $post['session_timestamp']
                    ]);
                }
            }
        }

        /** @var FastCity $domainCity */
        $domainCity = Yii::$app->getBehavior('geo')->domainCity;

        Yii::$app->gearman->getDispatcher()->background('ElkLog', [
            'model' => $model,
            'data' => [
                'ip' => Yii::$app->request->remoteIP,
                'userid' => (!Yii::$app->user->isGuest ? Yii::$app->user->id : Yii::$app->session->id),
                'domainId' => $domainCity->id ?? 0,
                'domainCode' => $domainCity ? $domainCity->code : 'main'
            ]
        ]);

        return [];
    }
}
