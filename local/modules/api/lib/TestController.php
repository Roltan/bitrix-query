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
        dd(ElementQuery::query()
            ->iblock(5)
            ->first());
    }
}