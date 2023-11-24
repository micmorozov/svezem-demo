<?php

namespace console\helpers\tomita\interfaces;

use Exception;
use GuzzleHttp\Client;

class RemoteTomitaQuering implements TomitaQuering
{
    public $url;

    /**
     * @param $text
     * @param $config
     * @return string
     */
    public function query($text, $config):string
    {
        $client = new Client();
        try{
            $response = $client->post($this->url, [
                'form_params' => [
                    'text' => $text,
                    'config' => $config
                ]
            ]);
        }catch (Exception $e){
            return false;
        }

        return $response->getBody()->getContents();
    }
}
