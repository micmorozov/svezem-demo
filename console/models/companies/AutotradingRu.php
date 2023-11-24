<?php
namespace console\models\companies;

use console\models\BaseCompany;
use Yii;

class AutotradingRu extends BaseCompany {

    public function parse() {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'http://www.autotrading.ru');
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:35.0) Gecko/20100101 Firefox/35.0');
        curl_setopt($ch, CURLOPT_REFERER, 'http://www.autotrading.ru');
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

        curl_setopt($ch, CURLOPT_URL, 'http://www.autotrading.ru/rates/calculate_v2/');
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:35.0) Gecko/20100101 Firefox/35.0');
        curl_setopt($ch, CURLOPT_REFERER, 'http://www.autotrading.ru');
        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            'target' => '1',
            'delivery' => 'ch1',
            'Calculate_form[from]' => $this->from_city_name,
            'Calculate_form[to]' => $this->to_city_name,
            'Calculate_form[weight]' => $this->weight,
            'Calculate_form[volume]' => floatval($this->width) * floatval($this->height) * floatval($this->depth),
            'Calculate_form[send_date]' => date('d.m.Y', time()),
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
        if($data) {
            $data = $data->find('div.sidebar_div');

            if (isset($data[0])) {
                $data = $data[0]->children(3);

                $children = $data->children(1);

                if (isset($children)) {
                    $data = $children->plaintext;
                    $this->res_status = self::STATUS_SUCCESS;
                    $this->cost = substr($data, 0, strpos($data, 'руб.') - 1);
                }
            }
        }

        curl_close($ch);
    }

}