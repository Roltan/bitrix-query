<?php

require __DIR__ . '/../bootstrap.php';

use Query\Tests\QueryTesterIblock;

$tester = new QueryTesterIblock(5);

$tester->assert(
    'operator precedence split correctly',
    fn() => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID'])
        ->whereRaw([
            '!ACTIVE' => 'N',
            '%NAME' => 'QB_TEST%',
            '>SORT' => 100
        ])
        ->get(),

    fn() => bxGet(
        [],
        [
            '!ACTIVE' => 'N',
            '%NAME' => 'QB_TEST%',
            '>SORT' => 100
        ],
        ['ID']
    )
);

$tester->assert(
    'duplicate keys last wins or merges',
    fn() => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID'])
        ->whereRaw([
            'ID' => 33,
            'ID' => 90
        ])
        ->get(),

    fn() => bxGet(
        [],
        ['ID' => 90],
        ['ID']
    )
);

$tester->assert(
    'empty string handling',
    fn() => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID'])
        ->whereRaw([
            'NAME' => ''
        ])
        ->get(),

    fn() => bxGet(
        [],
        ['NAME' => ''],
        ['ID']
    )
);

$tester->assert(
    'null value handling',
    fn() => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID'])
        ->whereRaw([
            'NAME' => null
        ])
        ->get(),

    fn() => bxGet(
        [],
        ['NAME' => null],
        ['ID']
    )
);

$tester->assert(
    'AND logic correctness',
    fn() => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID'])
        ->whereRaw([
            'ACTIVE' => 'Y',
            '>SORT' => 200,
            '<SORT' => 500
        ])
        ->get(),

    fn() => bxGet(
        [],
        [
            'ACTIVE' => 'Y',
            '>SORT' => 200,
            '<SORT' => 500
        ],
        ['ID']
    )
);

$tester->assert(
    'operator case normalization',
    fn() => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID'])
        ->whereRaw([
            '>SORT' => 100,
            '<SORT' => 500
        ])
        ->get(),

    fn() => bxGet(
        [],
        [
            '>SORT' => 100,
            '<SORT' => 500
        ],
        ['ID']
    )
);

$tester->assert(
    'ignore empty keys',
    fn() => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID'])
        ->whereRaw([
            '' => 'test',
            'ACTIVE' => 'Y'
        ])
        ->get(),

    fn() => bxGet(
        [],
        [
            'ACTIVE' => 'Y'
        ],
        ['ID']
    )
);