<?php
namespace console\models\companies;

use console\models\BaseCompany;
use simple_html_dom_node;
use Yii;
use yii\helpers\Json;

class AttentaRu extends BaseCompany {

    public function parse() {

        include_once(Yii::getAlias('@common/libs/simplehtmldom/') . 'simple_html_dom.php');

        $volume = floatval($this->width) * floatval($this->height) * floatval($this->depth);
        $date = date('d.m.y', time());

        $from = file_get_contents("http://www.attenta.ru/calculate/search.php?tarif=11&query={$this->from_city_name}&identifier=cities");
        if($from !== false && strpos($from, '<ul>') !== false) {
            $from = str_get_html($from);
            $from = $from->find('li');
            $from = $from[0];

            /** @var simple_html_dom_node $from */
            $from = $from->getAttribute('rel');
            $from = explode('_', $from);

            $to = file_get_contents("http://www.attenta.ru/calculate/search.php?tarif=11&query={$this->to_city_name}&identifier=cities");
            if($to !== false && strpos($to, '<ul>') !== false) {
                $to = str_get_html($to);
                $to = $to->find('li');
                $to = $to[0];

                /** @var simple_html_dom_node $to */
                $to = $to->getAttribute('rel');
                $to = explode('_', $to);

                $cargo_type = $from[6] == 0 ? 1 : 2;
                $total = file_get_contents("http://www.attenta.ru/calculate/calcIT.php?action=first&tarif=11&from[city]={$from[1]}&from[filial]={$from[0]}&from[zone]={$from[5]}&to[city]={$to[1]}&to[filial]={$to[0]}&to[zone]={$to[5]}&to[zoneName]=Город&cargo[volume]={$volume}&cargo[type]={$cargo_type}&cargo[weight]={$this->weight}&cargo[date]={$date}&control[gabarit]=0&control[pallet]=0&cargo[pallet]=&cargo[price]=&srv[radio][1]=12&srv[radio][2]=6&cargo[plomb]=&from[weight]=30&to[weight]=30&control[request]=0");
                $total = Json::decode($total);

                if(isset($total['price'])) {
                    $this->res_status = self::STATUS_SUCCESS;
                    $this->cost = $total['price'];
                }
            }
        }
    }

}