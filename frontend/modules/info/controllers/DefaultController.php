<?php

namespace frontend\modules\info\controllers;

use common\behaviors\NoSubdomain;
use common\models\Contacts;
use common\models\FastCity;
use common\models\LocationInterface;
use common\models\SearchPageTags;
use Yii;
use yii\web\Controller;
use frontend\modules\info\models\Feedback;
use common\helpers\TemplateHelper;

class DefaultController extends Controller
{

    public function behaviors()
    {
        return [
            NoSubdomain::class
        ];
    }
	/**
	 * Displays about page.
	 *
	 * @return mixed
	 */
	public function actionContacts(){
        /** @var LocationInterface $domainCity */
        $domainCity = Yii::$app->getBehavior('geo')->domainCity;

		$feedback = new Feedback();
		if ($feedback->load(Yii::$app->request->post()) && $feedback->validate()) {
			$feedback->subject = 'Обращение с сайта';
			if ($feedback->sendEmail(Yii::$app->params['adminEmail'])) {				
				Yii::$app->session->setFlash('success', 'Спасибо за ваше сообщение. Мы ответим на него максимально быстро.');
			} else {				
				Yii::$app->session->setFlash('error', 'Произошла ошибка при отправке сообщения. Попробуйте связаться с нами по контактам, указанным ниже');
			}		
			return $this->refresh();
		}

		$tags = [];
		if($domainCity){
		    $tags = SearchPageTags::findAll(['cityid' => $domainCity->getId()]);
        }

		return $this->render('contacts', [
		    'contacts' => Contacts::getLocalContacts(),
			'feedback' => $feedback,
            'pageTpl' => TemplateHelper::get("contacts-view"),
            'tags' => $tags
		]);
	}
}
