<?php

namespace common\components\vk;

use VK\OAuth\Scopes\VKOAuthGroupScope;
use VK\OAuth\Scopes\VKOAuthUserScope;
use VK\OAuth\VKOAuth;
use VK\OAuth\VKOAuthDisplay;
use VK\OAuth\VKOAuthResponseType;
use yii\base\Component;

class VkComponent extends Component
{
    public $access_token;
    public $client_id;
    public $group_id;
    public $client_secret;

    /**
     * УРЛ ролучения токена
     *
     * @return string
     */
    public function getTokenUrl()
    {
        $oauth = new VKOAuth();
        //$redirect_uri = 'https://oauth.vk.com/blank.html';
        $redirect_uri = 'https://example.com/vk';
        $display = VKOAuthDisplay::PAGE;
        $scope = [
            //VKOAuthUserScope::WALL,
            //VKOAuthUserScope::GROUPS,
            //VKOAuthUserScope::OFFLINE,
            VKOAuthGroupScope::MESSAGES
        ];
        $state = 'secret_state_code';
        $groups_ids = [196906158];

        $browser_url = $oauth->getAuthorizeUrl(
            VKOAuthResponseType::CODE,
            $this->client_id,
            $redirect_uri,
            $display,
            $scope,
            $state,
            $groups_ids
        );

       /* $browser_url = $oauth->getAuthorizeUrl(
            VKOAuthResponseType::TOKEN,
            $this->client_id,
            $redirect_uri,
            $display,
            $scope,
            $state,
            null,
            true
        );*/

        return $browser_url;
    }
}
