<?php

namespace frontend\modules\payment\components;

use ReflectionClass;
use ReflectionException;
use ReflectionProperty;

/**
 * Class QRPayment
 * @package frontend\modules\payment\components
 */
class QRPayment
{
    const FORMAT_ID = 'ST';
    const VERSION = '0001';

    const ENCODIDNG_WIN1251 = 1;
    const ENCODIDNG_UTF8 = 2;
    const ENCODIDNG_KOI8R = 3;

    const TECH_CODE_MOBLIE_CONNECTION = 1;
    const TECH_CODE_UTILITIES = 2;
    const TECH_CODE_INTERNET_TV = 9;
    const TECH_CODE_OTHER_SERVICES = 15;

    private $encoding = self::ENCODIDNG_UTF8;
    private $delimiter = '|';

    public $name = null;
    public $personalAcc = null;
    public $bankName = null;
    public $bic = null;
    public $correspAcc = null;

    public $sum = null;
    public $purpose = null;
    public $payeeINN = null;
    public $payerINN = null;
    public $persAcc = null;
    public $kpp = null;
    public $techCode = null;

    /**
     * @param $delimiter
     * @return $this
     */
    public function setDelimiter($delimiter)
    {
        $this->delimiter = $delimiter;
        return $this;
    }

    /**
     * @param $encoding
     * @return $this
     */
    public function setEncoding($encoding)
    {
        $this->encoding = $encoding;
        return $this;
    }

    /**
     * @return string
     * @throws ReflectionException
     */
    public function __toString()
    {
        $requiredProps = ['name', 'personalAcc', 'bankName', 'bic', 'correspAcc'];

        $blocks = [
            self::FORMAT_ID.self::VERSION.$this->encoding,
            'Name='.$this->name,
            'PersonalAcc='.$this->personalAcc,
            'BankName='.$this->bankName,
            'BIC='.$this->bic,
            'CorrespAcc='.$this->correspAcc
        ];

        $reflect = new ReflectionClass($this);
        $props = $reflect->getProperties(ReflectionProperty::IS_PUBLIC);

        foreach ($props as $prop) {
            $propName = $prop->getName();
            if (in_array($propName, $requiredProps)) {
                continue;
            }

            if ( !$this->{$propName}) {
                continue;
            }

            $blocks[] = $propName.'='.$this->{$propName};
        }

        return implode($this->delimiter, $blocks);
    }
}
