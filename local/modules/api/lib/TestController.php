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
            ->withElement('NEWS', function (ElementQuery $query) {
                $query->select(['ID', 'NAME']);
            });
        dd($query->get());
    }
}