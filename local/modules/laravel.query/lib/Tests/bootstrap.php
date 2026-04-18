<?php

$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/../../../../../');

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

\Bitrix\Main\Loader::includeModule('iblock');

function bxGet(array $order, array $filter, array $select, $nav = false): array
{
    $res = CIBlockElement::GetList(
        $order,
        $filter,
        false,
        $nav,
        $select
    );

    $rows = [];
    while ($r = $res->Fetch()) {
        $rows[] = $r;
    }

    return $rows;
}