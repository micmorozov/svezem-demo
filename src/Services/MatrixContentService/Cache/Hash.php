<?php

namespace Svezem\Services\MatrixContentService\Cache;

use DateTime;
use Exception;

class Hash
{
    /**
     * @param int|string|object|array $key
     * @return string
     * @throws Exception
     */
    public function getKeyHash($key) {
        $hashString = null;
        $type = gettype($key);

        if ($type === 'string') {
            $hashString = $key;
        }
        elseif ($type === 'array') {
            $hashString = implode(':', $key);
        }
        elseif ($type === 'NULL') {
            $hashString = 'null';
        }
        elseif ($key instanceof DateTime) {
            $hashString = $key->format('d.m.Y H:i:s');
        }
        else {
            $hashString = (string)$key;
        }

        return $hashString;
    }
}