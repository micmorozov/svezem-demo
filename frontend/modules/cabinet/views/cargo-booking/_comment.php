<?php

use yii\helpers\Html;

/** @var $cargo_id int */

$changeLink = Html::a('<BR>'.($comment?'изменить заметку о заказе':'оставить заметку о заказе'), '#', [
    'data-cargo_id' => $cargo_id,
    'class' => 'changeComment'
]);
printf("<span id='cargo_comment_%d' style='color: red'>%s %s</span><br><br>", $cargo_id, $comment, $changeLink);
?>
<div id="cargo_comment_edit_<?= $cargo_id ?>" style="display: none">
    <?= Html::textarea('price', $comment, [
        'style' => 'height:150px',
        'class' => 'form-control',
        'id' => 'comment_'.$cargo_id,
        'autocomplete' => 'off',
        'placeholder' => 'Укажите комментарий к грузу. Коментарий видите только Вы'
    ]);
    ?>
    <br>
    <?= Html::button('Сохранить', [
        'class' => 'search__btn content__btn comment_save',
        'data-cargo_id' => $cargo_id
    ]) ?>
    &nbsp;&nbsp;&nbsp;
    <?= Html::a('отменить', '#', [
        'class' => 'comment_cancel',
        'data-cargo_id' => $cargo_id
    ]) ?>
    <BR><BR>
</div>
