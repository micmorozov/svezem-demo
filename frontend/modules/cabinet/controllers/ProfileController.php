<?php

namespace frontend\modules\cabinet\controllers;

use Yii;
use common\models\Profile;
use yii\bootstrap\ActiveForm;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\Response;
use yii\data\ActiveDataProvider;
use frontend\modules\cabinet\models\UserEditForm;

/**
 * ProfileController implements the CRUD actions for Profile model.
 */
class ProfileController extends Controller
{
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'update' => ['post'],
                    'delete' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Creates a new Profile model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate($type)
    {
        $model = new Profile();
        $model->type = $type;

        // Перевозчик
        if($model->type > Profile::TYPE_SENDER){
            //Если уже есть профиль перевозчика
            if( Yii::$app->user->identity->transporterProfile ){
                return $this->redirect(['/cabinet/settings/']);
            }

            /* 05.07.2019 нигде не используется
            $model->transporterTypeIds = array_column(TransporterType::find()->asArray()->all(), 'id');
            */
        }

        // Отправитель
        if( $model->type == Profile::TYPE_SENDER ){
            //Если уже есть профиль отправителя
            if( Yii::$app->user->identity->senderProfile ){
                return $this->redirect(['/cabinet/settings/']);
            }
        }

        if( Yii::$app->request->isPost && $model->load(Yii::$app->request->post()) && $model->save() ){
            Yii::$app->session->setFlash('success', 'Профиль добавлен.');
            return $this->redirect(['/cabinet/settings/']);
        }

        return $this->render('create', [
            'model' => $model
        ]);
    }

    /**
     * Updates an existing Profile model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->created_by != Yii::$app->user->identity->id){
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ActiveForm::validate($model);
        }

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
			Yii::$app->session->setFlash('success', 'Данные сохранены.');
            return $this->redirect(['/cabinet/settings']);
        }
        // Если возникли ошибки, выводим первую из них
        if($errors = $model->getErrors()){
            Yii::$app->session->setFlash('error', array_shift($errors)[0]);
        }
		return $this->redirect(['/cabinet/settings']);

        throw new NotFoundHttpException;
    }

    /**
     * Deletes an existing Profile model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    /*public function actionDelete($id)
    {
        $model = $this->findModel($id);
        if ($model->created_by != Yii::$app->user->identity->id){
            throw new NotFoundHttpException('The requested page does not exist.');
        }
        $model->delete();

        return $this->redirect(['/cabinet/settings']);
    }*/

    /**
     * Finds the Profile model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Profile the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Profile::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
    
    public function actionProfileValidate($id){
    	$model = $this->findModel($id);
    	    	
    	if( $model->load(Yii::$app->request->post()) ) {
    		Yii::$app->response->format = Response::FORMAT_JSON;
    		return ActiveForm::validate($model);
    	}
    	
    	throw new NotFoundHttpException;
    }
}
