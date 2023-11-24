<?php
/** @var $transport Transport */
/** @var  $servicesName array */
/** @var $url string */
/** @var $daysBeforeEnd int  */

use common\models\Transport;
?>
Здравствуйте, <?= $transport->profile->contact_person ?>!<br><br>
Через <?= $daysBeforeEnd ?> дня оплаченные услуги:
<br>
<b><?= implode(', ', $servicesName); ?></b>
<br>
для транспорта <b><?= $transport->direction ?></b> завершатся.
<br><br>
Что бы не останавливать размещение, заранее продлите их!
<br><br>
Ссылка для продления: <?= $url ?>
<br><br>
По всем вопросам обращайтесь в нашу службу поддержки!<br><br>
С уважением,<br>
Команда Svezem.ru<br>
https://svezem.ru<br>
admin@svezem.ru<br>
8(800)201-23-56
