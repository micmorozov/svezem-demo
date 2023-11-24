<?php

namespace frontend\modules\transport\controllers;

use common\behaviors\NoSubdomain;
use common\models\Transport;
use Exception;
use frontend\modules\transport\models\TransportOwnerSearch;
use Yii;
use yii\base\ErrorException;
use yii\bootstrap\ActiveForm;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class DefaultController extends Controller
{
    //public $layout = '@frontend/views/layouts/cabinet.php';

    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['post'],
                ],
            ],
            'access' => [
                'class' => AccessControl::class,
                'only' => ['update', 'delete', 'mine'],
                'rules' => [
                    [
                        'actions' => ['update', 'delete', 'mine'],
                        'allow' => true,
                        'roles' => ['@'],
                    ]
                ],
            ],
            'nosubdomain' => [
                'class' => NoSubdomain::class,
                'only' => ['mine']
            ]
        ];
    }

    /**
     * Updates an existing Transport model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param $id
     * @return array|string|Response
     * @throws NotFoundHttpException
     * @throws ErrorException
     */
    public function actionUpdate($id)
    {
        //$this->layout = '@frontend/views/layouts/cabinet.php';

        Yii::$app->session->open();
        $model = $this->findModel($id);

        if ($model->created_by != Yii::$app->user->identity->id) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        if ($model->load(Yii::$app->request->post())) {
            // Если редактируем удаленный транспорт то статус надо сменить на активный
            $model->status = Transport::STATUS_ACTIVE;
            if ($model->save()) {
                Yii::$app->session->setFlash('Transport', 'updated');
                return $this->redirect(['/payment/transport', 'item_id' => $model->id]);
            }
        }

        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ActiveForm::validate($model);
        }

        return $this->render('@frontend/modules/account/views/default/signupTransport', [
            'model' => $model
        ]);
        //return $this->render('_form', ['model' => $model]);
    }

    /**
     * Deletes an existing Transport model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param $id
     * @return Response
     * @throws NotFoundHttpException
     * @throws Exception
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        if ($model->created_by != Yii::$app->user->identity->id) {
            throw new NotFoundHttpException('Запрашиваемая страница не найдена');
        }

        $model->status = Transport::STATUS_DELETED;
        $model->save();

        Yii::$app->session->setFlash('success', 'Ваш транспорт успешно удален');

        return $this->redirect(['/transport/mine']);
    }

    /**
     * Finds the Transport model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Transport the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Transport::findOne([
                'id' => $id,
                'created_by' => Yii::$app->user->id
            ])) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    public function actionMine()
    {
        $query = Transport::find()
            ->with(['cityFrom', 'cityTo', 'fullCargoCategories', 'profile'])
            ->where(['created_by' => Yii::$app->user->id])
            ->andWhere(['status' => Transport::STATUS_ACTIVE])
            ->orderBy(['id' => SORT_DESC]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'defaultPageSize' => Yii::$app->session->get('per-page',
                    Yii::$app->params['itemsPerPage']['defaultPageSize'])
            ]
        ]);

        return $this->render('mine', [
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionItemPosition($id)
    {
        $model = $this->findModel($id);

        Yii::$app->response->format = Response::FORMAT_JSON;

        return [
            'success' => $model->existPostion(),
            'html' => $this->renderPartial('../search/_position', [
                'model' => $model
            ])
        ];
    }
}
