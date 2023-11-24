<?php

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model Cargo */

use common\models\Cargo;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Создание заявки на отправку грузов';
$this->registerMetaTag([
    'name' => 'description',
    'content' => 'Создайте заявку на отправку груза и получайте предложения от перевозчиков с экономией до 70%'
]);
?>
<main class="container add-offer">
    <div class="page-title">
        <h1 class="h3 text-uppercase"><b>Добавления предложения о перевозке</b></h1>
    </div>
    <?php $form = ActiveForm::begin([
        'options' => ['enctype' => 'multipart/form-data']
    ]); ?>
    <div class="add-offer-bloks">
        <div class="add-offer-block-wrap">
            <div class="add-offer-add-img">
                <div class="add-offer-add-img__img-wrap">
                    <label for="upload-img">
                        <div class="add-offer-add-img__img" style="background-image: url(img/add-img.jpg);">

                        </div>
                    </label>
                    <span class="delete-img"><i class="fa fa-times" aria-hidden="true"></i></span>
                </div>
                <label class="add-offer-add-img__btn">Добавить фото <input type="file" id="upload-img"></label>
            </div>
            <div class="add-offer-block-body">
                <div class="add-offer-block">
                    <?= $form->field($model, 'name', [
                        'options' => [
                            'class'=>'form-field'
                        ],
                        'labelOptions'=> ['class' => "form-label"],
                        'inputOptions' => [
                            'class' => 'form-custom-input',
                            'placeholder' => "Марка машины"
                        ]
                    ])
                    ?>
                    <div class="form-field">
                        <label for="car-id" class="form-label">Идентификатор машины :</label>
                        <input id="car-id" type="text" class="form-custom-input" placeholder="Идентификатор машины">
                    </div>
                    <div class="form-field">
                        <label for="car-type" class="form-label">Марка машины :</label>
                        <input id="car-type" type="text" class="form-custom-input" placeholder="Марка машины">
                    </div>
                </div>
                <div class="add-offer-block">
                    <div class="add-offer-block-header">Характеристики кузова</div>
                    <div class="form-field mob-circle mob-circle-mz">
                        <label for="capacity" class="form-label">Объем кузова :</label>
                        <input id="capacity" type="text" class="form-custom-input" placeholder="Объем кузова">
                        <span>Мз</span>
                    </div>
                    <div class="form-field mob-circle mob-circle-kg">
                        <label for="carrying" class="form-label">Грузоподъемность :</label>
                        <input id=carrying" type="text" class="form-custom-input" placeholder="Грузоподъемность">
                        <span>Кг</span>
                    </div>
                    <div class="form-field">
                        <label for="view" class="form-label">Вид автотранспорта :</label>
                        <select  id="view" class="simple-select1">
                            <option value="0">Вид автотранспорта</option>
                            <option value="1">Вид 1</option>
                            <option value="2">Вид 2</option>
                            <option value="3">Вид 3</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="add-offer-block">
            <div class="add-offer-block-header">Способ загрузки</div>
            <ul class="checkbox-list">
                <li class="checkbox-list__item"><input type="checkbox" id="1"><label for="1"><span></span>Аппарели</label></li>
                <li class="checkbox-list__item"><input type="checkbox" id="2"><label for="2"><span></span>Без ворот</label></li>
                <li class="checkbox-list__item"><input type="checkbox" id="3"><label for="3"><span></span>Боковая</label></li>
                <li class="checkbox-list__item"><input type="checkbox" id="4"><label for="4"><span></span>Боковая с 2-х сторон</label></li>
                <li class="checkbox-list__item"><input type="checkbox" id="5"><label for="5"><span></span>Верхняя</label></li>
                <li class="checkbox-list__item"><input type="checkbox" id="6"><label for="6"><span></span>Гидроборт</label></li>
                <li class="checkbox-list__item"><input type="checkbox" id="7"><label for="7"><span></span>Задняя</label></li>
                <li class="checkbox-list__item"><input type="checkbox" id="8"><label for="8"><span></span>Манипулятор</label></li>
                <li class="checkbox-list__item"><input type="checkbox" id="9"><label for="9"><span></span>С бортами</label></li>
                <li class="checkbox-list__item"><input type="checkbox" id="10"><label for="10"><span></span>С кониками</label></li>
                <li class="checkbox-list__item"><input type="checkbox" id="11"><label for="11"><span></span>С обрешеткой</label></li>
                <li class="checkbox-list__item"><input type="checkbox" id="12"><label for="12"><span></span>Аппарели солной растеновкой</label></li>
                <li class="checkbox-list__item"><input type="checkbox" id="13"><label for="13"><span></span>Со снятием поперечных перекладин</label></li>
                <li class="checkbox-list__item"><input type="checkbox" id="14"><label for="14"><span></span>Со снятием стоек</label></li>
            </ul>
        </div>
        <div class="add-offer-block">
            <div class="add-offer-block-header">Вид перевозки</div>
            <ul class="checkbox-list more">
                <li class="checkbox-list__item"><input type="checkbox" id="21"><label for="21"><span></span>Домашние вещи</label></li>
                <li class="checkbox-list__item"><input type="checkbox" id="22"><label for="22"><span></span>Животные</label></li>
                <li class="checkbox-list__item"><input type="checkbox" id="23"><label for="23"><span></span>Коммерческая продукция</label></li>
                <li class="checkbox-list__item"><input type="checkbox" id="24"><label for="24"><span></span>Мусор, металлолом, макулатура</label></li>
                <li class="checkbox-list__item"><input type="checkbox" id="25"><label for="25"><span></span>Негабаритные грузы</label></li>
                <li class="checkbox-list__item"><input type="checkbox" id="26"><label for="26"><span></span>Опасные грузы</label></li>
                <li class="checkbox-list__item"><input type="checkbox" id="27"><label for="27"><span></span>Продукты питания</label></li>
                <li class="checkbox-list__item"><input type="checkbox" id="28"><label for="28"><span></span>Сборные грузы</label></li>
                <li class="checkbox-list__item"><input type="checkbox" id="29"><label for="29"><span></span>Сельхоз. продукция</label></li>
                <li class="checkbox-list__item"><input type="checkbox" id="31"><label for="31"><span></span>Строительные грузы</label></li>
                <li class="checkbox-list__item"><input type="checkbox" id="32"><label for="32"><span></span>Сыпучие и наливные грузы</label></li>
                <li class="checkbox-list__item"><input type="checkbox" id="33"><label for="33"><span></span>Транспортные средства</label></li>
                <li class="checkbox-list__item"><input type="checkbox" id="34"><label for="34"><span></span>Химическая продукция</label></li>
                <li class="checkbox-list__item"><input type="checkbox" id="35"><label for="35"><span></span>Хрупкие грузы</label></li>
                <li class="checkbox-list__item"><input type="checkbox" id="36"><label for="36"><span></span>Домашние вещи</label></li>
                <li class="checkbox-list__item"><input type="checkbox" id="37"><label for="37"><span></span>Животные</label></li>
                <li class="checkbox-list__item"><input type="checkbox" id="38"><label for="38"><span></span>Коммерческая продукция</label></li>
                <li class="checkbox-list__item"><input type="checkbox" id="39"><label for="39"><span></span>Мусор, металлолом, макулатура</label></li>
                <li class="checkbox-list__item"><input type="checkbox" id="40"><label for="40"><span></span>Негабаритные грузы</label></li>
                <li class="checkbox-list__item"><input type="checkbox" id="41"><label for="41"><span></span>Опасные грузы</label></li>
                <li class="checkbox-list__item"><input type="checkbox" id="42"><label for="42"><span></span>Продукты питания</label></li>
                <li class="checkbox-list__item"><input type="checkbox" id="43"><label for="43"><span></span>Сборные грузы</label></li>
                <li class="checkbox-list__item"><input type="checkbox" id="44"><label for="44"><span></span>Сельхоз. продукция</label></li>
                <li class="checkbox-list__item"><input type="checkbox" id="45"><label for="45"><span></span>Строительные грузы</label></li>
                <li class="checkbox-list__item"><input type="checkbox" id="46"><label for="46"><span></span>Сыпучие и наливные грузы</label></li>
                <li class="checkbox-list__item"><input type="checkbox" id="47"><label for="47"><span></span>Транспортные средства</label></li>
                <li class="checkbox-list__item"><input type="checkbox" id="48"><label for="48"><span></span>Химическая продукция</label></li>
                <li class="checkbox-list__item"><input type="checkbox" id="49"><label for="49"><span></span>Хрупкие грузы</label></li>
                <li class="checkbox-list__more"><span class="more-btn"><i class="fa fa-chevron-down" aria-hidden="true"></i> <span class="text">Еще</span></span></li>
            </ul>
        </div>
        <div class="add-offer-block">
            <div class="add-offer-block-header">Описание рейса</div>
            <div class="form-field">
                <label for="type" class="form-label">Тип рейса :</label>
                <select  id="type" class="simple-select1">
                    <option value="0">Тип рейса</option>
                    <option value="1">Тип 1</option>
                    <option value="2">Тип 2</option>
                    <option value="3">Тип 3</option>
                </select>
            </div>
            <div class="form-field">
                <label for="type" class="form-label">Города погрузки :</label>
                <select  id="type" class="simple-select1">
                    <option value="0">Города погрузки</option>
                    <option value="1">Тип 1</option>
                    <option value="2">Тип 2</option>
                    <option value="3">Тип 3</option>
                </select>
            </div>
            <div class="form-field">
                <label for="type" class="form-label">Города погрузки :</label>
                <select  id="type" class="simple-select1">
                    <option value="0">Города погрузки</option>
                    <option value="1">Тип 1</option>
                    <option value="2">Тип 2</option>
                    <option value="3">Тип 3</option>
                </select>
            </div>
            <div class="form-field">
                <label for="type" class="form-label hide-mob">Дата погрузки :</label>
                <div class="block date">
								<span>
									<label class="date-wrap from mob-circle mob-circle-calendar" for="date-from"><span class="mob-label">Дата погрузки :</span><input type="text" id="date-from" readonly="readonly"  class="form-custom-input date" placeholder="Дата погрузки С"></label>
									<label class="date-wrap to mob-circle mob-circle-calendar" for="date-to"><span class="mob-label">Дата погрузки :</span><input type="text" id="date-to" readonly="readonly" class="form-custom-input date" placeholder="Дата погрузки По"></label>
								</span>
                    <span class="radion-btn">
									<input type="checkbox" id="time">
									<label for="time"><span></span> Люболе время</label>
								</span>
                </div>
            </div>
        </div>
        <div class="add-offer-block conditions">
            <div class="add-offer-block-header">Условия оплаты</div>
            <div class="form-field">
                <label class="form-label">Стоимость перевозки :</label>
                <span class="block">
								<span class="part price mob-circle mob-circle-rub">
									<input type="text" class="form-custom-input" placeholder="Стоимость перевозки От">
									<span>Руб.</span>
								</span>
								<span class="part for">
									<span class="za">За</span>
									<select name="" id="" class="simple-select1">
										<option value="1">Км</option>
										<option value="2">М</option>
										<option value="3">См</option>
									</select>
									<span class="radion-btn">
										<input type="checkbox" id="nds">
										<label for="nds"><span></span> с НДС</label>
									</span>
								</span>
							</span>
            </div>
            <div class="form-field">
                <label class="form-label hide-mob no-padding">Способы оплаты :</label>
                <span class="block">
								<span class="part payment-method">
									<input id="card" type="checkbox"><label for="card"><span></span>Безналичный расчет</label>
									<input id="bank" type="checkbox"><label for="bank"><span></span>На карту банка</label>
									<input id="cash" type="checkbox"><label for="cash"><span></span>Наличные</label>
								</span>
							</span>
            </div>
        </div>
        <div class="add-offer-block">
            <div class="add-offer-block-header">Комментарий</div>
            <textarea class="form-custom-textarea"></textarea>
        </div>
        <div class="add-offer-block">
            <div class="add-offer-block-header">Регистрация</div>
            <button class="regi-btn">Я уже зарегестрирован на  svezem.ru</button>
            <div class="regi">
                <div class="form-field"><label for="" class="form-label">Контактное лицо :</label><input type="text" class="form-custom-input" placeholder="Контактное лицо"></div>
                <div class="form-field"><label for="" class="form-label">Город :</label><input type="text" class="form-custom-input" placeholder="Город"></div>
                <div class="form-field"><label for="" class="form-label">Телефон :</label><input type="tel" class="form-custom-input" placeholder="Телефон"></div>
                <div class="form-field"><label for="" class="form-label">E-mail :</label><input type="email" class="form-custom-input" placeholder="E-mail"></div>
            </div>
            <div class="login" style="display: none;">
                <div class="form-field"><label for="" class="form-label">Логин :</label><input type="text" class="form-custom-input" placeholder="Логин"></div>
                <div class="form-field"><label for="" class="form-label">Пароль :</label><input type="password" class="form-custom-input" placeholder="Пароль"></div>
            </div>
            <div class="form-field form-btn"><button class="form-custom-button">Добавить  предложение</button></div>
        </div>
    </div>
    <?php ActiveForm::end(); ?>
</main>
