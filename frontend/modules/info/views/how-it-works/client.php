<?php
use yii\helpers\Html;

$this->title = 'Как работает сервис свезём точка ру для отправителей груза';
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
        content: counters(li,".") ". ";
    }
</style>
<main class="content">
    <div class="container post">
        <div class="page-title">
            <h1 class="h3 text-uppercase"><b>Как это работает для отправителя?</b></h1>
        </div>

        <?= $this->render('_tags')?>

        <div class="post__item item clear">

            <div class="item__text">
                <p>Чтобы отправить груз, заказать вывоз отходов или другие услуги грузоперевозки, в сервисе созданы следующие инструменты:</p>
                <ol class="ol_counter">
                    <li style="list-style-type: none;"><b>Оформить заказ на перевозку (экономия до 70%).</b>

                        <br><br>

                        <ol class="ol_counter">
                            <li style="list-style-type: none;">На каждой странице сайта есть либо <b>форма</b> заказа перевозки, либо <b>кнопка</b> открытия этой формы.<br>
                                <?= Html::img('/img/info/how_it_works0.png', ['style'=>'max-width: 100%']) ?><br>
                                    Рисунок №1 (форма заказа)

                                <br><br>

                                <?= Html::img('/img/info/how_it_works1.png', ['style'=>'max-width: 100%']) ?><br>
                                Рисунок №2 (кнопка открытия формы заказа)
                            </li>

                            <br>
                            <li style="list-style-type: none;">После отправки заказа (нажать кнопку &laquo;Отправить&raquo;), заказ появляется в списке грузов.<br>
                                <?= Html::img('/img/info/how_it_works2.png', ['style'=>'max-width: 100%']) ?><br>
                                Рисунок №3 (заказ на перевозку)
                            </li>

                            <br>

                            <li style="list-style-type: none;">Всем перевозчикам и транспортным компаниям отправляется уведомление о том, что в сервисе появилась новая заявка.</li>

                            <br>

                            <li style="list-style-type: none;">Перевозчики и транспортные компании смотрят описание вашего заказа и предлагают свои услуги.</li>

                            <br><br>

                            <span style="color: #ff0000;"><em>За счет чего достигается экономия до 70%?</em></span>
                            <br><br>
                            <b>За счет честной конкуренции!</b> Перевозчики и транспортные компании, понимают, что Вашу заявку видят, все компании и частные перевозчики города, и чтобы получить заказ, необходимо предложить самые выгодные условия.
                        </ol>
                    </li>

                    <br>

                    <li style="list-style-type: none;"><b>Поиск перевозчика</b>

                        <br><br>

                        <ol class="ol_counter">
                            <li style="list-style-type: none;">В меню &laquo;Отправителю&raquo; есть пункт &laquo;Поиск перевозчика&raquo;<br>
                                <?= Html::img('/img/info/how_it_works3.png', ['style'=>'max-width: 100%']) ?><br>
                                Рисунок №4 (меню &laquo;Отправителю&raquo; - &laquo;Поиск перевозчика&raquo;)
                            </li>

                            <br>

                            <li style="list-style-type: none;">Перейдя по данному пункту и заполнив форму поиска, сервис отберет предложения перевозчиков, удовлетворяющие запросу.
                                <?= Html::img('/img/info/how_it_works4.png', ['style'=>'max-width: 100%']) ?><br>
                                Рисунок №5 (форма поиска перевозчика)
                            </li>
                        </ol>
                    </li>

                    <br>

                    <li style="list-style-type: none;"><b>Поиск транспортной компании</b>

                        <br><br>

                        <ol class="ol_counter">
                            <li style="list-style-type: none;">В меню &laquo;Отправителю&raquo; есть пункт &laquo;Транспортные компании&raquo;<br>
                                <?= Html::img('/img/info/how_it_works5.png', ['style'=>'max-width: 100%']) ?><br>
                                Рисунок №6 (меню &laquo;Отправителю&raquo; - &laquo;Транспортные компании&raquo;)
                                    <br><br><br>
                            </li>

                            <li style="list-style-type: none;">Перейдя по данному пункту и заполнив форму поиска, сервис отберет транспортные компании, удовлетворяющие запросу.<br>
                                <?= Html::img('/img/info/how_it_works6.png', ['style'=>'max-width: 100%']) ?><br>
                                Рисунок № 7 (форма поиска транспортной компании)
                            </li>
                        </ol>
                    </li>
                </ol>
            </div>
        </div>
    </div>
</main>
