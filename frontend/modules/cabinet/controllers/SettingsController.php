<?php

namespace frontend\modules\cabinet\controllers;

use common\behaviors\NoSubdomain;
use frontend\modules\cabinet\models\ProfileSearch;
use frontend\modules\cabinet\models\UserEditForm;
use Yii;
use yii\bootstrap\ActiveForm;
use yii\filters\AccessControl;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class SettingsController extends DefaultController
{

    /**
     * @inheritdoc
     */
    public function behaviors(){
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['index'],
                'rules' => [
                    [
                        'actions' => ['index'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'NoSubdomain' => [
                'class' => NoSubdomain::class
            ]
        ];
    }

    public function actionIndex(){
        $model = new UserEditForm();
        $searchModel = new ProfileSearch();

        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'model' => $model
        ]);
    }

    public function actionSave(){
        $model = new UserEditForm();
        $isLoaded = $model->load(Yii::$app->request->post());

        if(Yii::$app->request->isAjax){
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ActiveForm::validate($model);
        }

        if($isLoaded && $model->save()){
            Yii::$app->session->setFlash('success', "Пароль изменен. Ваш новый пароль для входа в сервис \"$model->password_new\"");
            return $this->redirect('/cabinet/settings');
        }

        throw new NotFoundHttpException;
    }

}
