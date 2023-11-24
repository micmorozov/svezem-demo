<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 07.02.19
 * Time: 11:14
 */

namespace frontend\components\urlRules;

use yii\web\UrlRule;

class RobotsRule extends UrlRule
{
    public function parseRequest($manager, $request)
    {
        $pathInfo = $request->getPathInfo();

        //если запрос НЕ соответсвует паттерну, то пропускаем
        if (!preg_match($this->pattern, $pathInfo, $matches)) {
            return false;
        }

        $route = $this->route;
        $params = [];

        return [$route, $params];
    }
}
