<?php

declare(strict_types=1);

namespace Api;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Security\Password;

class PasswordController extends Controller
{

    // обязательный метод предпроверки данных
    public function configureActions()
    {
        // сбрасываем фильтры по-умолчанию (Bitrix\Main\Engine\ActionFilter\Authentication() и Bitrix\Main\Engine\ActionFilter\HttpMethod() и Bitrix\Main\Engine\ActionFilter\Csrf()), предустановленные фильтры находятся в папке /bitrix/modules/main/lib/engine/actionfilter/
        return [
            'forgotPassword' => [
                'prefilters' => [],
                'postfilters' => []
            ],
            'checkCode' => [
                'prefilters' => [],
                'postfilters' => []
            ],
            'resetPassword' => [
                'prefilters' => [],
                'postfilters' => []
            ],
        ];
    }

    public function forgotPasswordAction()
    {
        global $USER;
        $email = $this->getRequest()->get('email');
        if (empty($email)) {
            http_response_code(422);
            throw new \Error('Переданы не все данные.', 422);
        }

        $arUser = \CUser::GetList(
            null,
            null,
            ['EMAIL' => $email]
        )->fetch();
        if (!$arUser) {
            http_response_code(404);
            throw new \Error("Пользователь с почтой $email не найден", 404);
        }

        $USER->SendPassword($arUser['LOGIN'], $email);
        http_response_code(200);
        return [];
    }

    public function checkCodeAction()
    {
        $email = $this->getRequest()->get('email');
        $passwordRecoveryCode = $this->getRequest()->get('code');

        if (empty($email) and empty($passwordRecoveryCode)) {
            http_response_code(422);
            throw new \Error('Переданы не все данные.', 422);
        }

        $arUser = \CUser::GetList(
            null,
            null,
            ['EMAIL' => $email]
        )->fetch();
        if (!$arUser) {
            http_response_code(404);
            throw new \Error("Пользователь с почтой $email не найден", 404);
        }

        $res = Password::equals($arUser["CHECKWORD"], $passwordRecoveryCode);
        if ($res) {
            http_response_code(200);
            return [];
        }
        http_response_code(400);
        throw new \Error('Проверочный код не верный', 400);
    }

    public function resetPasswordAction()
    {
        global $USER;
        $email = $this->getRequest()->get('email');
        $passwordRecoveryCode = $this->getRequest()->get('code');
        $password = $this->getRequest()->get('password');

        if (empty($email) and empty($passwordRecoveryCode) and empty($password)) {
            http_response_code(422);
            throw new \Error('Переданы не все данные.', 422);
        }

        $arUser = \CUser::GetList(
            null,
            null,
            ['EMAIL' => $email]
        )->fetch();
        if (!$arUser) {
            http_response_code(404);
            throw new \Error("Пользователь с почтой $email не найден", 404);
        }

        $res = Password::equals($arUser["CHECKWORD"], $passwordRecoveryCode);
        if ($res) {
            $arResult = $USER->ChangePassword($arUser['LOGIN'], $passwordRecoveryCode, $password, $password);
            if ((string)$arResult['TYPE'] === 'OK') {
                return [];
            }
            http_response_code(500);
            throw new \Error(str_replace('<br>', '', $arResult['MESSAGE']), 500);
        }
        http_response_code(400);
        throw new \Error('Проверочный код не верный', 400);
    }
}