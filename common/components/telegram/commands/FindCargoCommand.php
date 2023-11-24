<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use common\components\telegram\conversation\Conversation;
use common\helpers\CodeHelper;
use common\helpers\refflog\ReffLogObject;
use common\helpers\RouteHelper;
use common\helpers\Utils;
use common\helpers\UTMHelper;
use common\models\Cargo;
use console\jobs\CargoRoute;
use Exception;
use Longman\TelegramBot\Commands\AdminCommand;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;
use Longman\TelegramBot\Entities\Location;
use Longman\TelegramBot\Entities\Update;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Telegram;
use Yii;
use yii\base\View;
use yii\helpers\Html;

/**
 * User "/findcargo" command
 */
class FindCargoCommand extends AdminCommand
{
    /**#@+
     * {@inheritdoc}
     */
    protected $name = 'findcargo';
    protected $description = 'Найти грузы рядом с Вами в радиусе 50 км';
    protected $usage = '/findcargo';
    protected $version = '0.1';
    protected $searchLink;

    /** @var Conversation */
    protected $conversation;
    /**#@-*/

    //Кол-во грузов на странице
    const ITEM_ON_PAGE = 5;

    //Кол-во страниц
    const MAX_SHOW_PAGE = 2;

    const NEXT_PAGE_TEXT = 'Еще 5 ▶';

    public function __construct(Telegram $telegram, Update $update = null)
    {
        parent::__construct($telegram, $update);

        $this->searchLink = 'Больше грузов на '.Html::a('svezem.ru',
                'https://'.Yii::getAlias('@domain').'/cargo/search/').' '.json_decode('"'."\xE2\xA4\xB4".'"');
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {

        $message = $this->getMessage();
        $user = $message->getFrom();
        $user_id = $user->getId();
        $chat_id = $message->getChat()->getId();

        //Conversation start
        $this->conversation = Yii::$container->get(Conversation::class);
        $this->conversation->init($user_id, $chat_id, $message->getCommand());

        $data = [
            'chat_id' => $chat_id
        ];

        //Получаем заметки из беседы
        $note = &$this->conversation->note;

        //Если геолокация не передана в данном сообщении
        //и не была определена ранее, то отображаем кнопку с запросом геоданных
        if (is_null($loc = $message->getLocation()) && !$loc = $note->getLocation()) {
            $data['reply_markup'] = (new Keyboard(
                (new KeyboardButton("Передать мое местоположение"))->setRequestLocation(true)
            ))
                ->setOneTimeKeyboard(true)
                ->setResizeKeyboard(true);
            $data['text'] = 'Мне нужно знать Ваше местоположение. Нажмите на кнопку, что бы передать его. Либо прикрепите нужную геопозицию';

            return Request::sendMessage($data);
        } else {
            //Данные из беседы
            $convData = $note->getData();

            $page = $convData['page']??1;
            $cargo_ids = $convData['cargo_ids']??$this->getCargoIds($loc, self::ITEM_ON_PAGE*self::MAX_SHOW_PAGE);

            //Пришло сообщение следующей страницы
            if ($message->getText() == self::NEXT_PAGE_TEXT) {
                $page++;
            }

            $note->setLocation($loc);
            $note->setData([
                'cargo_ids' => $cargo_ids,
                'page' => $page
            ]);
            //Сохраняем заметки беседы
            $this->conversation->update();

            $count = count($cargo_ids);
            $offset = self::ITEM_ON_PAGE*($page - 1);

            $pageCargoIds = array_slice($cargo_ids, $offset, self::ITEM_ON_PAGE);

            $cargos = Cargo::find()
                ->where(['id' => $pageCargoIds])
                ->orderBy(['id' => SORT_DESC])
                ->all();

            if (empty($cargos)) {
                $emoji = "\xF0\x9F\x98\xAD";
                $data['text'] = 'Грузов рядом с вами не найдено '.json_decode('"'.$emoji.'"');
                $data['text'] .= "\n\n".$this->searchLink;
                $data['reply_markup'] = ['remove_keyboard' => true];

                $this->conversation->delete();

                return Request::sendMessage(array_merge($data, [
                    'parse_mode' => 'html',
                    'disable_web_page_preview' => true
                ]));
            } else {
                //Если результат на несколько страниц и это первая,
                //то показываем кнопку прокрутки
                if ($page == 1 && $page*self::ITEM_ON_PAGE < $count) {
                    $btn = new KeyboardButton(self::NEXT_PAGE_TEXT);
                    $keyBoard = new Keyboard($btn);
                    $keyBoard->setResizeKeyboard(true);
                    $data['reply_markup'] = $keyBoard;
                    $data['text'] = 'Грузы рядом с вами:';
                    $response = Request::sendMessage($data);
                }

                //Отсылаем сообщения по каждому грузу
                foreach ($cargos as $cargo) {
                   /* $reffObj = new ReffLogObject();
                    $reffObj->source = 'telegram';
                    $reffObj->source_type = 'findcargo';
                    $reffObj->addExtra('telegramId', $user_id);
                    $reff_log = CodeHelper::createReffLogCode($reffObj);*/

                    /*$showContactUrl = Utils::addParamToUrl($cargo->url, [
                        'reff_log' => $reff_log
                    ]);*/

                    $showContactUrl = UTMHelper::genUTMLink($cargo->url, [
                        'utm_source'    => 'telegram',
                        'utm_medium'    => 'bot',
                        'utm_compaign'  => 'findcargo'
                    ]);

                    $view = new View();
                    $tplMsg = json_decode($view->renderFile('@common/components/telegram/templates/cargo.php', [
                        'cargo' => $cargo,
                        'relativeTime' => true,
                        'contactUrl' => $showContactUrl
                    ]), true);
                    $data['text'] = $tplMsg['text'];

                    try{
                        $response = Request::sendMessage(array_merge($data, [
                            'parse_mode' => 'html',
                            'disable_web_page_preview' => true
                        ]));
                    } catch (Exception $e){
                    }
                }

                //Если страниц больше нет, то удаляем конпку
                if ($page*self::ITEM_ON_PAGE >= $count) {
                    $this->conversation->delete();

                    $data['text'] = $this->searchLink;
                    $data['reply_markup'] = ['remove_keyboard' => true];
                    $response = Request::sendMessage(array_merge($data, [
                        'parse_mode' => 'html',
                        'disable_web_page_preview' => true
                    ]));
                }

                return $response;
            }
        }
    }

    /**
     * @param Location $loc
     * @param bool $limit
     * @return array|mixed
     */
    private function getCargoIds(Location $loc, $limit = false)
    {
        $res = RouteHelper::getRedis()->georadius(
            CargoRoute::REDIS_CARGO_LOCATION,
            $loc->getLongitude(),
            $loc->getLatitude(),
            50,
            'km'
        );

        rsort($res);

        if ($limit) {
            return array_slice($res, 0, $limit);
        }

        return $res;
    }
}
