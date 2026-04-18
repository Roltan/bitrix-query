<?php

namespace Api;

use Bitrix\Main\Engine\Controller;
use Query\ElementQuery;

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
        $query = ElementQuery::query()
            ->iblock(5)
            ->select(['ID'])
            ->whereRaw([[
                'LOGIC' => 'OR',
                ['%NAME' => 'QB_TEST'],
                ['><SORT' => [100, 300]]
            ]]);
        dd($query->get());
    }
}