<?php

if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php'))
    require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/include/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/include/consts.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/include/events.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/include/autoloader.php';