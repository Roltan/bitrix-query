<?php

declare(strict_types=1);

namespace Api;

use Api\Middlewares\JwtMiddleware;
use Api\Services\UserService;
use Bitrix\Main\Engine\Controller;
use Api\Services\TokenService;

class UserController extends Controller
{
    protected $tokenService;

    // обязательный метод предпроверки данных
    public function configureActions()
    {
        $this->tokenService = new TokenService();

        // сбрасываем фильтры по-умолчанию (Bitrix\Main\Engine\ActionFilter\Authentication() и Bitrix\Main\Engine\ActionFilter\HttpMethod() и Bitrix\Main\Engine\ActionFilter\Csrf()), предустановленные фильтры находятся в папке /bitrix/modules/main/lib/engine/actionfilter/
        return [
            'info' => [
                'prefilters' => [new JwtMiddleware()],
                'postfilters' => []
            ],
            'changeUser' => [
                'prefilters' => [new JwtMiddleware()],
                'postfilters' => []
            ],
        ];
    }

    public function infoAction()
    {
        $user = UserService::getUser();

        return [
            'name' => $user['NAME'],
            'email' => $user['EMAIL'],
            'phone' => $user['PERSONAL_PHONE'],
        ];
    }

    public function changeUserAction()
    {
        global $USER;
        $email = $this->getRequest()->get('email');
        $name = $this->getRequest()->get('name');
        $phone = $this->getRequest()->get('phone');
        $userId = $USER->GetID();

        if(empty($email) and empty($name) and empty($phone)) {
            http_response_code(422);
            throw new \Error('Не передано ни одного нового значения', 422);
        }

        $fields = [];

        if ($email)
            $fields['EMAIL'] = $email;

        if ($name)
            $fields['NAME'] = $name;

        if ($phone)
            $fields['PERSONAL_PHONE'] = $phone;

        if ($USER->Update($userId, $fields)) {
            http_response_code(200);
            return [];
        } else {
            http_response_code(500);
            throw new \Exception($USER->LAST_ERROR, 500);
        }
    }
}
