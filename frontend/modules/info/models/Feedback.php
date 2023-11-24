<?php

namespace frontend\modules\info\models;

use ferrumfist\yii2\recaptcha\ReCaptchaValidator;
use Yii;
use yii\base\Model;

/**
 * ContactForm is the model behind the contact form.
 */
class Feedback extends Model
{
  public $name;
  public $email;
  public $body;
  public $subject;
  public $reCaptcha;

    /**
    * @inheritdoc
    */
    public function rules() {
        return [
            ['body', 'filter','filter'=>'\yii\helpers\HtmlPurifier::process'],
            [['name', 'email', 'body', 'reCaptcha'], 'required'],
            ['email', 'email'],
            ['reCaptcha', ReCaptchaValidator::class]
        ];
    }

    /**
    * @inheritdoc
    */
    public function attributeLabels() {
    return [
        'name' => 'Ваше имя',
        'body' => 'Текст сообщения',
        'email' => 'Ваш email',
        'subject' => 'Тема'
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
      ->setReplyTo([$this->email => $this->name])
      ->setFrom($email)
      ->setSubject($this->subject)
      ->setTextBody($this->body)
      ->send();
  }
}
