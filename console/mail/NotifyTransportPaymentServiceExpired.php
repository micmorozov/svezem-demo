<?php
/** @var $transport Transport */
/** @var  $servicesName array */
/** @var $url string */

use common\models\Transport;
?>
Здравствуйте, <?= $transport->profile->contact_person ?>!<br><br>
Оплаченные услуги
<br>
<b><?= implode(', ', $servicesName); ?></b>
<br>
для транспорта <b><?= $transport->direction ?></b> выключены.
<br><br>
Теперь ваше объявление стало менее заметно для заказчиков!<br>
Что бы вновь выделиться, продлите услуги!
<br><br>
Ссылка для продления: <?= $url ?>
<br><br>
По всем вопросам обращайтесь в нашу службу поддержки!<br>
С уважением,<br>
Команда Svezem.ru<br>
https://svezem.ru<br>
admin@svezem.ru<br>
8(800)201-23-56
