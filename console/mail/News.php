<?php

use common\helpers\UTMHelper;
use common\helpers\UserHelper;

// ИД новостной рассылки
$mailingid = $mailingid??0;
// ИД получателя рассылки
$userid = $userid??false;

// Для каждого пользователя генерим спец ссылку на отписку
$unsubscribe_url = false;
if($userid) {
    $unsubscribe_url = UserHelper::createAuthorizeUrl($userid, '/cabinet/mailing/', false, 3*86400);
    $unsubscribe_url = UTMHelper::genUTMLink($unsubscribe_url, [
        'utm_source'    => 'email',
        'utm_medium'    => 'news',
        'utm_compaign'  => 'unsubscribe',
        'utm_content'   => (int)$mailingid
    ]);
}
?>
Здравствуйте!<br>
<br>
<?=nl2br($body) ?>
<br><br>
С уважением,<br>
Команда Svezem.ru<br>
https://svezem.ru<br>
admin@svezem.ru<br>
8(800)201-23-56<br>
<br>
<?php if($unsubscribe_url):?>
<a href="<?=$unsubscribe_url?>">Отписаться</a> от рассылки
<?php endif ?>