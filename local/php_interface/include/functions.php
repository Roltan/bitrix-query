<?php

//use \Bitrix\Iblock\IblockTable;

if (!function_exists('custom_mail') && COption::GetOptionString("webprostor.smtp", "USE_MODULE") == "Y") {
    function custom_mail($to, $subject, $message, $additional_headers = '', $additional_parameters = '')
    {
        if (\Bitrix\Main\Loader::includeModule("webprostor.smtp")) {
            $smtp = new CWebprostorSmtp("s1");
            $result = $smtp->SendMail($to, $subject, $message, $additional_headers, $additional_parameters);

            if ($result)
                return true;
            else
                return false;
        }
    }
}

if (!function_exists('dump')) {
    function dump($arr)
    {
        echo '<pre>';
        print_r($arr);
        echo '</pre>';
    }
}

if (!function_exists('dd')) {
    function dd($var)
    {
        dump($var);
        die();
    }
}