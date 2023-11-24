<?php

use yii\helpers\Html;

$this->title = 'Как работает сервис свезём точка ру для перевозчиков груза';
$this->registerMetaTag([
    'name' => 'description',
    'content' => 'Описание работы сервиса грузоперевозок свезём точка ру'
]);

?>
<style>
    ol.ol_counter {
        /* убираем стандартную нумерацию */
        list-style: none;
        /* Идентифицируем счетчик и даем ему имя li. Значение счетчика не указано - по умолчанию оно равно 0 */
        counter-reset: li;
    }

    @media (max-width: 1199px) {
        ol.ol_counter {
            /* убираем стандартную нумерацию */
            list-style: none;
            /* Идентифицируем счетчик и даем ему имя li. Значение счетчика не указано - по умолчанию оно равно 0 */
            counter-reset: li;
            padding-left: 0 !important;
        }
    }

    ol.ol_counter li:before {
        /* Определяем элемент, который будет нумероваться — li. Псевдоэлемент before указывает, что содержимое, вставляемое при помощи свойства content, будет располагаться перед пунктами списка. Здесь же устанавливается значение приращения счетчика (по умолчанию равно 1). */
        counter-increment: li;
        /* С помощью свойства content выводится номер пункта списка. counters() означает, что генерируемый текст представляет собой значения всех счетчиков с таким именем. Точка в кавычках добавляет разделяющую точку между цифрами, а точка с пробелом добавляется перед содержимым каждого пункта списка */
        content: counters(li, ".") ". ";
    }
</style>
<main class="content">
    <div class="container post">
        <div class="page-title">
            <h1 class="h3 text-uppercase"><b>Как это работает для перевозчика?</b></h1>
        </div>
        <?= $this->render('_tags') ?>
        <div class="post__item item clear">
            <div class="item__text">
                <p>Чтобы получить заказ на доставку груза, вывоз отходов или другие услуги грузоперевозки, в сервисе
                    созданы следующие инструменты:</p>
                <ol class="ol_counter">
                    <li style="list-style-type: none;"><b>&laquo;Разместить объявление&raquo; (больше заказов при
                            минимуме действий).</b>

                        <br><br>

                        <ol class="ol_counter">
                            <li style="list-style-type: none;">В меню &laquo;Перевозчику&raquo; есть пункт &laquo;Предложить
                                услуги&raquo;<br>
                                <?= Html::img('/img/info/how_it_works7.png', ['style' => 'max-width: 100%']) ?><br>
                                Рисунок №1 (меню &laquo;Перевозчику&raquo; - &laquo;Предложить услуги&raquo;)
                            </li>

                            <br>
                            <li style="list-style-type: none;">После заполнения формы и отправки предложения, объявление
                                появляется в списке перевозчиков (меню &laquo;Отправителю&raquo; - &laquo;Поиск
                                перевозчика&raquo;)<br>
                                <?= Html::img('/img/info/how_it_works8.png', ['style' => 'max-width: 100%']) ?><br>
                                Рисунок №2 (объявление перевозчика)
                                <br><br>

                            </li>

                            <span style="color: #ff0000;"><em>Если Вы работает по разным направлениям, то создав под каждое направление отдельное объявление, вы получите больше клиентов, так как заказчики ищут перевозчика на конкретное направление.</em></span><br><br>
                            <span style="color: #ff0000;"><em>Добавляйте фотографию транспорта, это повысит привлекательность объявления по отношению к конкурентам.</em></span>
                        </ol>
                    </li>

                    <br>

                    <li style="list-style-type: none;"><b>Поиск груза</b>
                        <br><br>

                        <ol class="ol_counter">
                            <li style="list-style-type: none;">В меню &laquo;Перевозчику&raquo;, есть пункт &laquo;Поиск
                                груза&raquo;<br>
                                <?= Html::img('/img/info/how_it_works9.png', ['style' => 'max-width: 100%']) ?><br>
                                Рисунок №3 (меню &laquo;Перевозчику&raquo; - &laquo;Поиск груза&raquo;)
                            </li>

                            <br>

                            <li style="list-style-type: none;">Перейдя по данному пункту и заполнив форму поиска, сервис
                                отберет заказы на грузоперевозку, удовлетворяющие запросу.<br>
                                <?= Html::img('/img/info/how_it_works10.png', ['style' => 'max-width: 100%']) ?><br>
                                Рисунок №4 (форма поиска груза)
                            </li>
                        </ol>
                    </li>
                </ol>
            </div>
        </div>
    </div>
</main>
