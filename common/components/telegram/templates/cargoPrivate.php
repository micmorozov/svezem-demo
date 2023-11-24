<?php
/**
 * Шаблон для личных сообщений с возможностью сразу получить телефон, если есть оплаченный тариф
 */

use common\components\bookingService\Service;
use common\helpers\Convertor;
use common\helpers\UTMHelper;
use common\models\Cargo;
use Longman\TelegramBot\Entities\InlineKeyboard;
use yii\helpers\Html;

/** @var Cargo $cargo */
/** @var bool $relativeTime */
/** @var string $contactUrl */
/** @var string $subscribeUrl */

$relativeTime = $relativeTime ?? false;
/** @var Service $bookingService */
$bookingService = $bookingService ?? false; // Сервис бронирований
$utmParams = $utmParams ?? [];

///// Эмодзи /////////
$emCalendar = json_decode('"'."\xF0\x9F\x93\x85".'"');
$emDirection = json_decode('"'."\xE2\x86\x94".'"');
$emDescription = json_decode('"'."\xE2\x84\xB9".'"');
$emPhone = json_decode('"'."\xE2\x98\x8E".'"');
$emOption = json_decode('"' . "\xF0\x9F\x94\xA7" . '"');
$emWarrning = json_decode('"' . "\xE2\x9A\xA0" . '"');
$emArrowDown = json_decode('"' . "\xE2\xAC\x87" . '"');
//////////////////////

//Текст заказа
$order = 'Заказ от ' . Yii::$app->formatter->asDate($cargo->created_at, 'dd.MM.y');
if( $relativeTime ){
    $order .= ' (' .Yii::$app->formatter->asRelativeTime($cargo->created_at).')';
}

//Текст направления
if ($cargo->city_from == $cargo->city_to) {
    $direction = 'По городу: '.$cargo->cityFrom->title_ru . ', ' . $cargo->regionFrom->title_ru;
} else {
    $direction = 'Направление: '.$cargo->cityFrom->title_ru.' - '.$cargo->cityTo->title_ru;
    if ($cargo->distance) {
        $direction .= ', '.Convertor::distance($cargo->distance).', '.Convertor::time($cargo->duration);
    }
}

$text = $emCalendar. ' ' . $order . "\n\n";
$text .= $emDirection . ' ' . $direction . "\n\n";
$text .= $emDescription . ' ' . $cargo->description . "\n\n";

$text .= Html::a($emPhone . " Посмотреть контакты заказчика", $contactUrl) . "\n\n";

// Оплачена ли услуга у юзера
if($bookingService->canBooking()){
    // Остаток показов на текущие сутки
    $dayLimitRemain = $bookingService->dayLimitRemain();
    if($dayLimitRemain > 0) {
        $text .= $emWarrning . " По вашему тарифу вы можете открыть контакты у {$bookingService->getDayLimit()} новых заказов в сутки. Сегодня у вас осталось {$dayLimitRemain} просмотра контактов.";
        $keyboard = new InlineKeyboard([
            [
                'text' => 'Показать телефон',
                'callback_data' => json_encode([
                    'cmd' => 'CargoBooking',
                    'cargoid' => $cargo->id,
                    'userid' => $bookingService->getUserId()
                ])
            ]
        ]);
    }else{
        $text .= $emWarrning . " <b>Суточный лимит на просмотр новых контактов исчерпан. Чтобы просмотреть контакты, дождитесь следующих суток или смените тариф.</b>";
        $keyboard = new InlineKeyboard([
            [
                'text' => 'Сменить тариф',
                'url' => UTMHelper::genUTMLink('https://svezem.ru/cargo/booking/', $utmParams)
            ]
        ]);
    }
}else {
    $text .= $emWarrning . "<b>Вы могли бы прямо здесь получить телефон заказчика{$emArrowDown}, если бы подключили доступ к заявкам...</b>";

    $keyboard = new InlineKeyboard([
        [
            'text' => 'Подключить доступ к заявкам',
            'url' => UTMHelper::genUTMLink('https://svezem.ru/cargo/booking/', $utmParams)
        ]
    ]);
}
$keyboard->setResizeKeyboard(true);

echo json_encode([
    'text' => $text,
    'reply_markup' => $keyboard
]);
?>