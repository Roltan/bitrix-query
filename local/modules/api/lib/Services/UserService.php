<?php

namespace Api\Services;

class UserService
{
    public static function getUser($userId = null)
    {
        if (is_null($userId)) {
            global $USER;
            $userId = $USER->getId();
        }

        return \CUser::GetList(
            false, false,
            ['ID' => $userId],
            ['SELECT' => []]
        )->fetch();
    }
}