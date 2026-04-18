<?php

declare(strict_types=1);

namespace Api;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Security\Password;
use Api\Middlewares\JwtMiddleware;
use Api\Services\TokenService;

class AuthController extends Controller
{
    protected $tokenService;

    public function configureActions()
    {
        $this->tokenService = new TokenService();

        return [
            'register' => [
                'prefilters' => [],
                'postfilters' => []
            ],
            'login' => [
                'prefilters' => [],
                'postfilters' => []
            ],
            'refresh' => [
                'prefilters' => [],
                'postfilters' => []
            ],
            'logout' => [
                'prefilters' => [],
                'postfilters' => []
            ]
        ];
    }

    public function registerAction()
    {
        global $USER;
        $request = $this->getRequest();
        $email = $request->get('email');
        $password = $request->get('password');
        $fullName = $request->get('fullName');
        $phone = $request->get('phone') ?? '';

        if (!$email || !$fullName) {
            $this->returnError(422, "Поля email и fullName обязательны");
        }

        // Проверка существования пользователя
        $dbUser = \CUser::GetList(('ID'), ('ASC'), ['=EMAIL' => $email]);
        if ($dbUser->Fetch()) {
            $this->returnError(409, 'Пользователь с таким email уже существует');
        }

        // Создание пользователя
        $arName = explode(" ", $fullName);

        $arResult = $USER->Register(
            $email,
            $arName[1] ?? '',
            $arName[0],
            $password,
            $password,
            $email
        );

        if ((string)$arResult['TYPE'] != 'OK') {
            $this->returnError(500, str_replace('<br>', '', $arResult['MESSAGE']));
        }

        // Получаем созданного пользователя
        $arUser = \CUser::GetByLogin($email)->fetch();

        // Обновляем дополнительные поля
        $updateFields = [
            'PERSONAL_PHONE' => $phone,
        ];

        if (isset($arName[2])) {
            $updateFields['SECOND_NAME'] = $arName[2];
        }

        if (!(new \CUser)->Update($arUser['ID'], $updateFields)) {
            $this->returnError(500, 'Произошла ошибка при изменении информации пользователя');
        }

        // После успешной регистрации создаем токены
        $USER->Authorize($arUser['ID'], false, false);
        $tokens = $this->tokenService->createTokens($arUser);

        return [
            'message' => 'Регистрация успешна',
            'userId' => $arUser['ID'],
            'tokens' => $tokens
        ];
    }

    public function loginAction()
    {
        global $USER;
        $request = $this->getRequest();
        $email = $request->get('email');
        $password = $request->get('password');

        if (!$email || !$password) {
            $this->returnError(422, "Поля email и password обязательны");
        }

        // Ищем пользователя
        $arUser = \CUser::GetList(
            null,
            null,
            ['EMAIL' => $email]
        )->fetch();

        if (!$arUser) {
            $this->returnError(404, "Пользователь с почтой $email не найден");
        }

        // Пытаемся авторизоваться
        $arAuthResult = $USER->Login($arUser['LOGIN'], $password, 'N', 'Y');

        if ($arAuthResult['TYPE'] == 'ERROR') {
            $this->returnError(401, str_replace('<br>', '', $arAuthResult['MESSAGE']));
        }

        // Авторизуем пользователя в системе
        $USER->Authorize($arUser['ID'], false, false);

        // Создаем токены
        $tokens = $this->tokenService->createTokens($arUser);

        return [
            'userId' => $arUser['ID'],
            'tokens' => $tokens
        ];
    }

    public function refreshAction()
    {
        $request = $this->getRequest();
        $refreshToken = $request->get('refreshToken');

        if (!$refreshToken) {
            $this->returnError(422, "Refresh token обязателен");
        }

        $tokens = $this->tokenService->refreshTokens($refreshToken);

        return [
            'tokens' => $tokens
        ];
    }

    public function logoutAction()
    {
        $request = $this->getRequest();
        $refreshToken = $request->get('refreshToken');

        $this->tokenService->logout($refreshToken);

        return [];
    }

    private function returnError($code, $message)
    {
        http_response_code($code);
        throw new \Error($message, $code);
    }
}