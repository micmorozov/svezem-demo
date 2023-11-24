<?php


namespace console\helpers\tomita\interfaces;


interface TomitaQuering
{
    /**
     * @param $text
     * @param $config
     * @return string
     */
    public function query($text, $config):string;
}
