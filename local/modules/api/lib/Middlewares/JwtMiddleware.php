<?php

declare(strict_types=1);

namespace Api\Middlewares;

use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Api\Services\TokenService;

/**
 * @author Антон Егоров
 */
class JwtMiddleware extends ActionFilter\Base
{
    /**
     * Реализация middleware в соответствии документацией по ActionFilter
     *
     * @link https://dev.1c-bitrix.ru/api_d7/bitrix/main/engine/actionfilter/index.php
     * @param Event $event
     * @return void
     */
    public function onBeforeAction(Event $event)
    {
        $validate = TokenService::validateAccessToken();
        if (!$validate['success']) {
            http_response_code(401);
            throw new \Error($validate['message'], 401);
        }
        return null;
    }
}
