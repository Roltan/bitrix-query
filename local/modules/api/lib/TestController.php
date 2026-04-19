<?php

namespace Api;

use Bitrix\Main\Engine\Controller;
use Query\ElementQuery;
use Query\SectionQuery;

class TestController extends Controller
{
    public function configureActions()
    {
        return [
            'test' => [
                'prefilters' => [],
                'postfilters' => []
            ],
        ];
    }

    public function testAction()
    {
        $collected = [];
        \Query\ElementQuery::query()
            ->iblock(5)
            ->select(['ID'])
            ->orderBy('ID', 'ASC')
            ->chunk(2, function ($items) use (&$collected) {
                foreach ($items as $item) {
                    $collected[] = $item['ID'];
                }
            });

        return $collected;
//        $res = \CIBlockElement::GetList(
//            ['ID' => 'ASC'],
//            ['IBLOCK_ID' => 5],
//            false,
//            [
//                'nPageSize' => 2,
//                'iNumPage' => 3,
//            ],
//            ['ID']
//        );
//
//        $rows = [];
//        while ($r = $res->Fetch()) {
//            $rows[] = $r;
//        }
//
//        return $rows;
    }
}