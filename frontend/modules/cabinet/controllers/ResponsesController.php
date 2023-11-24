<?php

namespace frontend\modules\cabinet\controllers;

use frontend\modules\cabinet\models\ResponseSearch;
use Yii;
use yii\filters\AccessControl;

class ResponsesController extends DefaultController
{

  /**
   * @inheritdoc
   */
  public function behaviors() {
    return [
      'access' => [
        'class' => AccessControl::className(),
        'only' => ['index'],
        'rules' => [
          [
            'actions' => ['index'],
            'allow' => true,
            'roles' => ['@'],
          ],
        ],
      ],
    ];
  }

  public function actionIndex() {
    $searchModel = new ResponseSearch();

    $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
    return $this->render('index', [
      'dataProvider' => $dataProvider,
      'searchModel' => $searchModel
    ]);
  }

}
