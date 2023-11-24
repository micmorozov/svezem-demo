<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Правила работы с перевозчиком на svezem.ru';
$this->registerMetaTag([
    'name' => 'description',
    'content' => 'Описание этапов работы заказчика(грузоотправителя) с перевозчиком на svezem.ru. Шаблон договора'
]);
?>
<main class="content">
    <div class="container post">
        <div class="page-title">
            <h1 class="h3 text-uppercase"><b>Правила работы с перевозчиком</b></h1>
        </div>
        <p>
        Договариваясь о перевозке старайтесь соблюдать несколько простых правил. Это позволит сэкономить время, деньги и нервы.
        </p>

        <ol>
            <li>Обязательно подпишите с перевозчиком договор на перевозку. Шаблон договора можно <?= Html::a('скачать здесь','https://' . Yii::getAlias('@domain') . Url::toRoute(['/download', 'file'=>'Проект договора по грузоотправителю.docx']), ['rel' => 'nofollow']) ?></li>
            <li>Внимательно проверьте паспортные данные перевозчика</li>
            <li>Составьте опись груза в соответствии с договором</li>
            <li>После загрузки сделайте фотографии машины, так, что бы был виден гос. номер автомобиля</li>
        </ol>
    </div>
</main>