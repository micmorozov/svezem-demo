<?php
/**
 * Created by PhpStorm.
 * User: Морозов Михаил
 * Date: 01.07.2016
 * Time: 14:38
 */

namespace console\controllers;

use common\models\User;
use common\helpers\StringHelper;
use Yii;
use yii\console\Controller;


class SmsController extends Controller
{
	/**
	 * Отправляем всем уже внесенным с авито СМС о том что их траспорт добавлен
	 */
	public function actionSendSms2AllFromAvito($moderid){
		// Получаем всех кого уже перенесли с авито, но СМС не отправили
		$users = User::find()->where(['created_by' => $moderid])->all();
		foreach($users as $user){
			if(!$user['phone']) continue;

			$passwd = StringHelper::str_rand(6, '1234567890'); // Генерим пароль
			$hpasswd = Yii::$app->security->generatePasswordHash($passwd); // Хэшируем пароль

			if(User::updateAll(['password_hash' => $hpasswd], 'id='.$user['id'])) {
				// Отправляем СМС сообщение владельцу транспорта, о том что он добавлен
				$smsMsg = "Ваше объявление с avito.ru бесплатно размещено на svezem.ru Для просмотра предложений используйте: логин - {$user['phone']}, пароль - {$passwd}";
				Yii::$app->sms->smsSend($user['phone'], $smsMsg);
			}

		}

		return 0;
	}
}