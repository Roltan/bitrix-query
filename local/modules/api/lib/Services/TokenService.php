<?php

namespace Api\Services;

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class TokenService
{
    const ACCESS_TOKEN_LIFETIME = 3600; // 1 час
    const REFRESH_TOKEN_LIFETIME = 2592000; // 30 дней
    const MAX_REFRESH_TOKENS_PER_USER = 5;

    // Создание пары токенов
    public function createTokens($arUser)
    {
        if (empty($arUser)) {
            throw new \Error('Пользователь не найден', 400);
        }

        $sessionId = bitrix_sessid_get();
        $tokenId = bin2hex(random_bytes(16));

        // Access Token
        $accessToken = $this->generateAccessToken($arUser['ID'], $sessionId);

        // Refresh Token
        $refreshToken = $this->generateRefreshToken($arUser['ID'], $tokenId, $sessionId);

        // Сохраняем только ID refresh token для возможности отзыва
        $this->storeRefreshTokenId($arUser['ID'], $tokenId);

        return [
            'accessToken' => $accessToken,
            'refreshToken' => $refreshToken
        ];
    }

    // Генерация Access Token
    private function generateAccessToken($userId, $sessionId)
    {
        $payload = [
            'iss' => $_SERVER['HTTP_HOST'] ?? 'localhost',
            'iat' => time(),
            'exp' => time() + self::ACCESS_TOKEN_LIFETIME,
            'sub' => $userId,
            'session' => $sessionId,
            'type' => 'access',
            'jti' => bin2hex(random_bytes(8)) // JWT ID для уникальности
        ];

        return JWT::encode($payload, $this->getSecretKey(), 'HS256');
    }

    // Генерация Refresh Token
    private function generateRefreshToken($userId, $tokenId, $sessionId)
    {
        $payload = [
            'iss' => $_SERVER['HTTP_HOST'] ?? 'localhost',
            'iat' => time(),
            'exp' => time() + self::REFRESH_TOKEN_LIFETIME,
            'sub' => $userId,
            'session' => $sessionId,
            'type' => 'refresh',
            'jti' => $tokenId,
            'version' => '1.0' // Для возможности инвалидации всех токенов при смене версии
        ];

        // Используем отдельный ключ для refresh токенов (опционально)
        return JWT::encode($payload, $this->getRefreshSecretKey(), 'HS256');
    }

    // Храним только ID refresh токенов для возможности отзыва
    private function storeRefreshTokenId($userId, $tokenId)
    {
        $user = new \CUser;
        $currentTokens = $user->GetByID($userId)->Fetch()['UF_ACTIVE_REFRESH_TOKENS'] ?? '[]';
        $tokensArray = json_decode($currentTokens, true) ?: [];

        // Ограничиваем количество активных refresh токенов
        if (count($tokensArray) >= self::MAX_REFRESH_TOKENS_PER_USER) {
            // Удаляем самый старый токен
            array_shift($tokensArray);
        }

        // Сохраняем только ID и время создания
        $tokensArray[] = [
            'id' => $tokenId,
            'created' => time(),
            'expires' => time() + self::REFRESH_TOKEN_LIFETIME
        ];

        $fields = ["UF_ACTIVE_REFRESH_TOKENS" => json_encode($tokensArray)];
        $user->Update($userId, $fields);
    }

    // Проверяем, не отозван ли refresh token
    private function isRefreshTokenRevoked($userId, $tokenId)
    {
        $user = new \CUser;
        $userData = $user->GetByID($userId)->Fetch();

        if (empty($userData['UF_ACTIVE_REFRESH_TOKENS'])) {
            return true; // Все токены отозваны
        }

        $tokensArray = json_decode($userData['UF_ACTIVE_REFRESH_TOKENS'], true);

        foreach ($tokensArray as $token) {
            if ($token['id'] === $tokenId && $token['expires'] > time()) {
                return false; // Токен активен
            }
        }

        return true; // Токен не найден или истек
    }

    // Обновление токенов
    public function refreshTokens($refreshToken)
    {
        try {
            // Декодируем refresh token
            $decoded = JWT::decode($refreshToken, new Key($this->getRefreshSecretKey(), 'HS256'));

            if ($decoded->type !== 'refresh') {
                http_response_code(400);
                throw new \Error('Неверный тип токена', 400);
            }

            $userId = (int) $decoded->sub;
            $tokenId = $decoded->jti;

            // Проверяем существование пользователя
            $user = \CUser::GetByID($userId)->Fetch();
            if (!$user) {
                http_response_code(404);
                throw new \Error('Пользователь не найден', 404);
            }

            // Проверяем, не отозван ли токен
            if ($this->isRefreshTokenRevoked($userId, $tokenId)) {
                http_response_code(401);
                throw new \Error('Refresh token отозван', 401);
            }

            // Отзываем старый refresh token
            $this->revokeRefreshToken($userId, $tokenId);

            // Создаем новую пару токенов
            $sessionId = bitrix_sessid_get();
            $newTokenId = bin2hex(random_bytes(16)); // Генерируем новый ID для refresh token
            $newAccessToken = $this->generateAccessToken($userId, $sessionId);
            $newRefreshToken = $this->generateRefreshToken($userId, $newTokenId, $sessionId);

            // Сохраняем НОВЫЙ refresh token ID, а не старый
            $this->storeRefreshTokenId($userId, $newTokenId);

            return [
                'accessToken' => $newAccessToken,
                'refreshToken' => $newRefreshToken,
            ];

        } catch (\Firebase\JWT\ExpiredException $e) {
            throw new \Error('Refresh token истек', 401);
        } catch (\Exception $e) {
            throw new \Error('Недействительный токен: ' . $e->getMessage(), 400);
        }
    }

    // Отзыв refresh token
    private function revokeRefreshToken($userId, $tokenId)
    {
        $user = new \CUser;
        $userData = $user->GetByID($userId)->Fetch();

        if (empty($userData['UF_ACTIVE_REFRESH_TOKENS'])) {
            return;
        }

        $tokensArray = json_decode($userData['UF_ACTIVE_REFRESH_TOKENS'], true);
        $newTokens = array_filter($tokensArray, function($token) use ($tokenId) {
            return $token['id'] !== $tokenId;
        });

        $fields = ["UF_ACTIVE_REFRESH_TOKENS" => json_encode(array_values($newTokens))];
        $user->Update($userId, $fields);
    }

    // Отзыв всех refresh токенов пользователя
    public function revokeAllUserTokens($userId)
    {
        $user = new \CUser;
        $fields = ["UF_ACTIVE_REFRESH_TOKENS" => json_encode([])];
        $user->Update($userId, $fields);

        // Также можно добавить версию токена, чтобы инвалидировать все существующие
        $this->incrementTokenVersion($userId);
    }

    // Инкремент версии токенов (для принудительной инвалидации)
    private function incrementTokenVersion($userId)
    {
        $user = new \CUser;
        $currentVersion = $user->GetByID($userId)->Fetch()['UF_TOKEN_VERSION'] ?? 1;
        $fields = ["UF_TOKEN_VERSION" => (int)$currentVersion + 1];
        $user->Update($userId, $fields);
    }

    // Валидация Access Token
    public static function validateAccessToken()
    {
        $accessToken = self::getTokenFromHeader();

        if (!$accessToken) {
            return ['success' => false, 'message' => 'Токен отсутствует', 'code' => 'NO_TOKEN'];
        }

        try {
            $service = new self();
            $decoded = JWT::decode($accessToken, new Key($service->getSecretKey(), 'HS256'));

            if ($decoded->type !== 'access') {
                return ['success' => false, 'message' => 'Неверный тип токена', 'code' => 'INVALID_TYPE'];
            }

            // Проверяем существование пользователя
            $userId = (int) $decoded->sub;
            if (!\CUser::GetByID($userId)->Fetch()) {
                return ['success' => false, 'message' => 'Пользователь не найден', 'code' => 'USER_NOT_FOUND'];
            }

            // Проверяем версию токена (опционально)
            if (!$service->validateTokenVersion($userId, $decoded)) {
                return ['success' => false, 'message' => 'Токен устарел', 'code' => 'OUTDATED_TOKEN'];
            }

            // Авторизуем пользователя в Bitrix
            global $USER;
            if (!$USER->IsAuthorized()) {
                $USER->Authorize($userId, false, false);
            }

            return [
                'success' => true,
                'userId' => $userId,
                'session' => $decoded->session,
                'jti' => $decoded->jti ?? null
            ];

        } catch (\Firebase\JWT\ExpiredException $e) {
            return ['success' => false, 'message' => 'Токен истек', 'code' => 'TOKEN_EXPIRED'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Недействительный токен: ' . $e->getMessage(), 'code' => 'INVALID_TOKEN'];
        }
    }

    // Проверка версии токена (если используется)
    private function validateTokenVersion($userId, $decodedToken)
    {
        // Если не используем версионирование, всегда возвращаем true
        $user = new \CUser;
        $userData = $user->GetByID($userId)->Fetch();
        $currentVersion = $userData['UF_TOKEN_VERSION'] ?? 1;

        // Если в токене есть версия, проверяем её
        if (isset($decodedToken->version)) {
            return $decodedToken->version >= $currentVersion;
        }

        return true;
    }

    // Получение токена из заголовка
    private static function getTokenFromHeader()
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';

        if (empty($authHeader)) {
            // Пробуем получить из другого заголовка
            $authHeader = $headers['authorization'] ?? '';
        }

        if (preg_match('/Bearer\s+(\S+)/i', $authHeader, $matches)) {
            return trim($matches[1]);
        }

        // Также можно проверить query parameter для отладки
        $request = Application::getInstance()->getContext()->getRequest();
        return $request->getQuery('access_token') ?: false;
    }

    // Получение ID пользователя из валидного токена
    public static function getUserId()
    {
        $validation = self::validateAccessToken();

        if (!$validation['success']) {
            throw new \Error($validation['message'], 401);
        }

        return $validation['userId'];
    }

    // Выход из системы
    public function logout($refreshToken = null)
    {
        global $USER;

        try {
            // Если передан refresh token, отзываем его
            if ($refreshToken) {
                try {
                    $decoded = JWT::decode($refreshToken, new Key($this->getRefreshSecretKey(), 'HS256'));
                    if ($decoded->type === 'refresh') {
                        $this->revokeRefreshToken((int)$decoded->sub, $decoded->jti);
                    }
                } catch (\Exception $e) {
                    // Игнорируем ошибки декодирования при logout
                }
            }

            // Отзываем все токены текущего пользователя
            $currentUserId = $USER->GetID();
            if ($currentUserId) {
                $this->revokeAllUserTokens($currentUserId);
            }

            // Выход из системы Bitrix
            $USER->Logout();

            return true;

        } catch (\Exception $e) {
            throw new \Error('Ошибка при выходе: ' . $e->getMessage(), 500);
        }
    }

    // Получение секретного ключа
    private function getSecretKey()
    {
        // Используем разные ключи для разных окружений
        return defined('JWT_SECRET_KEY') ? JWT_SECRET_KEY : 'your-default-access-secret-key-change-in-production';
    }

    // Получение секретного ключа для refresh токенов
    private function getRefreshSecretKey()
    {
        // Можно использовать тот же ключ или отдельный для refresh токенов
        return defined('JWT_REFRESH_SECRET_KEY')
            ? JWT_REFRESH_SECRET_KEY
            : $this->getSecretKey() . '-refresh'; // Добавляем суффикс
    }

    // Очистка устаревших refresh token ID
    public function cleanupExpiredTokens()
    {
        $dbUsers = \CUser::GetList(['ID' => 'ASC'], ['ACTIVE' => 'Y']);

        while ($user = $dbUsers->Fetch()) {
            if (!empty($user['UF_ACTIVE_REFRESH_TOKENS'])) {
                $this->cleanupUserTokens($user['ID']);
            }
        }
    }

    private function cleanupUserTokens($userId)
    {
        $user = new \CUser;
        $userData = $user->GetByID($userId)->Fetch();

        if (empty($userData['UF_ACTIVE_REFRESH_TOKENS'])) {
            return;
        }

        $tokensArray = json_decode($userData['UF_ACTIVE_REFRESH_TOKENS'], true);
        $validTokens = array_filter($tokensArray, function($token) {
            return $token['expires'] > time();
        });

        $fields = ["UF_ACTIVE_REFRESH_TOKENS" => json_encode(array_values($validTokens))];
        $user->Update($userId, $fields);
    }
}