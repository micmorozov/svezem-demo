<?php
namespace console\models\companies;

use console\models\BaseCompany;
use Yii;

class DtkMRu extends BaseCompany {

    public function parse() {

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'http://www.dtk-m.ru/calc/');
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:35.0) Gecko/20100101 Firefox/35.0');
        curl_setopt($ch, CURLOPT_REFERER, 'http://www.dtk-m.ru');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLINFO_HEADER_OUT, false);

        $data = curl_exec($ch);
        curl_close($ch);

        $cookie = '';
        $pattern = '/Set-Cookie:(.*?)\n/';
        if (preg_match($pattern, $data, $result))
            $cookie = $result[1];

        // ----------------------------------------
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'http://www.dtk-m.ru/_templates/calc_standalone.php');
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:35.0) Gecko/20100101 Firefox/35.0');
        curl_setopt($ch, CURLOPT_REFERER, 'http://www.dtk-m.ru');
        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            'from' => $this->from_city_name . ' (багажная скорость)',
            'to' => $this->to_city_name,
            'volume' => floatval($this->width) * floatval($this->height) * floatval($this->depth),
            'weight' => $this->weight,
        ]);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
        curl_setopt($ch, CURLOPT_COOKIE, $cookie);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLINFO_HEADER_OUT, false);

        include_once(Yii::getAlias('@common/libs/simplehtmldom/') . 'simple_html_dom.php');
        $data = curl_exec($ch);
        $data = str_get_html($data);
        $data = $data->find('p');

        if(!empty($data) && isset($data[2])) {
            $data = $data[2]->plaintext;
            $data = explode(' ', $data);
            $this->res_status = self::STATUS_SUCCESS;
            $this->cost = $data[1];
        }

        curl_close($ch);
    }
}