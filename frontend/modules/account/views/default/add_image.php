<?php
/* @var $this yii\web\View */
/* @var $form ActiveForm */

/* @var $model Transport */

use common\models\Transport;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

?>
<?=$form->field($model, 'deleteImage')->hiddenInput(['class' => 'del_tr_image'])->label(false) ?>
    <div class="add-offer-add-img" style="position: relative">
        <div class="add-offer-add-img__img-wrap">
            <label for="upload-img" style="width: 100%;padding-top: 15px; display: block">
                <div class="add-offer-add-img__img" id="previewImage" style="background-image: url(<?= $model->getImagePath(null, true) ?>);"></div>
            </label>
            <span class="delete-img" id="delImage"><i class="fa fa-times" aria-hidden="true"></i></span>
        </div>
        <label class="add-offer-add-img__btn">Добавить фото
            <?= $form->field($model, 'image', ['template' => '{input}{error}'])->fileInput(['id' => "upload-img"]) ?>
        </label>
    </div>
<?php $this->registerJs("
function readURL(input) {
    if (input && input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function (e) {
            $('#previewImage').css('background-image', 'url('+ e.target.result+')');
            $('.del_tr_image').val(0);
        }
        reader.readAsDataURL(input.files[0]);
    }
    else{
        $('#previewImage').css('background-image', 'url()');
    }
}
$('#upload-img').change(function(){
    readURL(this);
});
$('#delImage').click(function(){
    $('#upload-img').val('');
    readURL();
    
    //помечаем удаление картинки
    $('.del_tr_image').val(1);
});
"); ?>