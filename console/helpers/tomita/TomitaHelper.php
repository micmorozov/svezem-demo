<?php

namespace console\helpers\tomita;

use console\helpers\tomita\interfaces\TomitaQuering;
use console\helpers\tomita\parsers\CargoNameParser;
use console\helpers\tomita\parsers\CategoriesParser;

class TomitaHelper
{
    private $quering;

    /**
     * TomitaHelper constructor.
     * @param TomitaQuering $quering
     */
    public function __construct(TomitaQuering $quering)
    {
        $this->quering = $quering;
    }

    /**
     * @param string $text
     * @param string $configPath
     * @return bool|false|mixed|string
     */
    public function query($text, $configPath)
    {
        return $this->quering->query($text, $configPath);
    }

    /**
     * @param $text
     * @return CategoriesParser
     */
    public function parseCategories($text)
    {
        $response = $this->query($text, 'categories.proto');
        return new CategoriesParser($response);
    }

    /**
     * @param $text
     * @return CargoNameParser
     */
    public function parseCargoName($text)
    {
        $response = $this->query($text, 'cargoName.proto');
        return new CargoNameParser($response);
    }
}
