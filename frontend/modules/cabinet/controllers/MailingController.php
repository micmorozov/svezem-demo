<?php


namespace frontend\modules\cabinet\controllers;


use common\behaviors\NoSubdomain;
use common\models\User;
use yii\filters\AccessControl;
use Yii;

class MailingController  extends DefaultController
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

    /**
     * Управление подпиской
     */
    public function actionIndex()
    {
        $model = User::findOne(Yii::$app->user->id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', "Подписка успешно изменена");
            return $this->redirect(['/cabinet/mailing']);
        }

        return $this->render('index', [
            'model' => $model,
        ]);
    }

}