<?php

namespace frontend\modules\tk\models;

use Yii;
use yii\base\Model;

/**
 * ContactForm is the model behind the contact form.
 */
class CreateTkForm extends Model
{
  public $name;
  public $email;
  public $body;
  public $url;
  public $phone;
  public $subject;

  /**
   * @inheritdoc
   */
  public function rules() {
    return [
      // name, email, subject and body are required
      [['name', 'email', 'url', 'phone','body'], 'required'],
      // email has to be a valid email address
      ['email', 'email'],
      ['url', 'url']
    ];
  }

  /**
   * @inheritdoc
   */
  public function attributeLabels() {
    return [
      'name' => 'Наименование транспортной компании',
      'email' => 'Ваш email',
      'url' => 'url адрес сайта',
      'body' => 'Текст сообщения',
      'phone' => 'Ваш контактный телефон'
    ];
  }

  /**
   * Sends an email to the specified email address using the information collected by this model.
   *
   * @param  string $email the target email address
   * @return boolean whether the email was sent
   */
  public function sendEmail($email) {
    return Yii::$app->mailer->compose()
      ->setTo($email)
      ->setFrom([$this->email => $this->name])
      ->setSubject($this->subject)
      ->setTextBody('Адрес сайта: ' . $this->url . "\nТел: " . $this->phone . "\nСообщение: " . $this->body)
      ->send();
  }
}
