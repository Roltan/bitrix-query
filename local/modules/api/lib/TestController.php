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
        return \Query\ElementQuery::query()
            ->iblock(5)
            ->orWhere('ID', 33)
            ->select(['ID'])
            ->getFilter();
    }
}