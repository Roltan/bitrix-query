<?php

// Определяем корневую директорию
$_SERVER['DOCUMENT_ROOT'] = __DIR__ . '/../../..';
$DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];

// Подключаем необходимые константы и пролог
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_CRONTAB', true);
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

require_once __DIR__ . '/autoloader.php';