<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 28.09.17
 * Time: 9:22
 */

namespace frontend\components\field;


use yii\helpers\Html;
use yii\widgets\ActiveField;

class ExtendField extends ActiveField
{
    /**
     * @var string the template that is used to arrange the label, the input field, the error message and the hint text.
     * The following tokens will be replaced when [[render()]] is called: `{label}`, `{input}`, `{error}` and `{hint}`.
     */
    public $template = null;//"{label}\n<div class='form__field-wrapper'>{error}{input}\n{hint}\n</div>";

    public $divWrapperStyle = '';

    public $errorExtraClass = '';

    public function init()
    {
        parent::init();

        if( !$this->template )
            $this->template = "{label}\n"
                .Html::beginTag('span', [
                    'class' => 'form__field-wrapper ',
                    'style' => $this->divWrapperStyle
                ])
                ."{error}{input}\n{hint}\n"
                .Html::endTag('span');
    }

    public function error($options = [])
    {
        if ($options === false) {
            $this->parts['{error}'] = '';
            return $this;
        }

        //текст ошибки
        $error = $this->model->getFirstError($this->attribute);

        //тело блока ошибки
        $errBody = "
    <div class='tooltip-arrow' style='left: 50%;'></div>
    <div class='tooltip-inner'>{$error}</div>
    ";

        //скрыть/показать блок ошибки
        $styleBlock = "display: ".($error == '' ? "none" : "block");

        $this->parts['{error}'] =  Html::tag('div', $errBody, [
            'class'=>'tooltip fade top in form__field_error '.$this->errorExtraClass,
            'role' => 'tooltip',
            'style' => $styleBlock
        ]);

        $form = $this->form->options['id'];
        $attr = $this->attribute;

        if ($this->form->enableClientScript) {
            //запускаем скрипт для клиентской проверки
            ErrMsgAsset::register($this->form->view);
            $this->form->view->registerJs("errFormMsg.add('$form', '$attr');");
        }

        return $this;
    }

    public function textInput($options = [])
    {
        //если в модели есть ошибка, то добавляем класс к input
        if($this->model->hasErrors($this->attribute)){
            if( isset($this->inputOptions['class']) )
                $this->inputOptions['class'] .= ' error';
            else
                $this->inputOptions['class'] = 'error';
        }

        return parent::textInput($options);
    }
}