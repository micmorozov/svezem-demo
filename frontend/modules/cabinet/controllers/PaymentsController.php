<?php

namespace frontend\modules\cabinet\controllers;

use Yii;
use common\models\Payment;
use frontend\modules\cabinet\models\PaymentsSearch;
use yii\web\NotFoundHttpException;

/**
 * PaymentsController implements the CRUD actions for PaymentService model.
 */
class PaymentsController extends DefaultController
{
    /**
     * Lists all PaymentService models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new PaymentsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single PaymentService model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Finds the PaymentService model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Payment the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Payment::find()->where(['id' => $id])->with(['paymentSystem', 'serviceRate'])->one()) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('Запрашиваемая страница не существует.');
        }
    }
}
