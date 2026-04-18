<?php

use Bitrix\Main\Routing\RoutingConfigurator;
use Api\AuthController;
use Api\UserController;
use Api\PasswordController;

\Bitrix\Main\Loader::includeModule('laravel.query');

return function (RoutingConfigurator $routes) {
    $routes->prefix('api')->group(function (RoutingConfigurator $routes) {
        $routes->prefix('auth')->group(function (RoutingConfigurator $routes) {
            $routes->post('login', [AuthController::class, 'login']);
            $routes->post('register', [AuthController::class, 'register']);
            $routes->post('refresh', [AuthController::class, 'refresh']);
            $routes->post('logout', [AuthController::class, 'logout']);
        });

        $routes->prefix('user')->group(function (RoutingConfigurator $routes) {
            $routes->prefix('password')->group(function (RoutingConfigurator $routes) {
                $routes->post('forgot', [PasswordController::class, 'forgotPassword']);
                $routes->post('check', [PasswordController::class, 'checkCode']);
                $routes->post('reset', [PasswordController::class, 'resetPassword']);
            });

            $routes->get('info', [UserController::class, 'info']);
            $routes->post('info', [UserController::class, 'changeUser']);
        });

        $routes->get('test', [\Api\TestController::class, 'test']);
    });
};