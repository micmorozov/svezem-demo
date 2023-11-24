<?php

namespace frontend\modules\account\widgets;

use common\models\LoginSignup;
use yii\base\Widget;

class FormLogin extends Widget
{
    public $model;

    public $form;

    public function init()
    {
        parent::init();

        if (!$this->model) {
            $this->model = new LoginSignup();
        }
    }

    public function run()
    {
        return $this->render('form_login', [
            'model' => $this->model,
            'form' => $this->form
        ]);
    }
}