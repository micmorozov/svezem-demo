<?php
namespace frontend\modules\account\models;

use common\models\User;
use yii\base\InvalidParamException;
use yii\base\Model;
use Yii;

/**
 * Password reset form
 */
class ResetPasswordForm extends Model
{
  public $password;
  public $password_repeat;

  /**
   * @var User
   */
  private $_user;


  /**
   * Creates a form model given a token.
   *
   * @param  string $token
   * @param  array $config name-value pairs that will be used to initialize the object properties
   * @throws InvalidParamException if token is empty or not valid
   */
  public function __construct($token, $config = []) {
    if (empty($token) || !is_string($token)) {
      throw new InvalidParamException('Password reset token cannot be blank.');
    }
    $this->_user = User::findByPasswordResetToken($token);
    if (!$this->_user) {
      throw new InvalidParamException("Данная ссылка не действительна. Перейдите к форме восстановления пароля чтобы получить новую.");
    }
    parent::__construct($config);
  }

  public function attributeLabels() {
    return [
      'password' => 'Пароль',
      'password_repeat' => 'Пароль повторно',
    ];
  }

  /**
   * @inheritdoc
   */
  public function rules() {
    return [

      ['password', 'required'],
      ['password', 'string', 'min' => 6, 'max' => 25],


      ['password_repeat', 'required'],
      ['password_repeat', 'compare', 'compareAttribute' => 'password', 'message' => 'Пароли должны совпадать'],
    ];
  }

  /**
   * Resets password.
   *
   * @return boolean if password was reset.
   */
  public function resetPassword() {
    $user = $this->_user;
    $user->setPassword($this->password);
    $user->removePasswordResetToken();

    return $user->save(false);
  }
}
