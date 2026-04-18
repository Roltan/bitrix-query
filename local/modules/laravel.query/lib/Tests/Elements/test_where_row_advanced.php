<?php

require __DIR__ . '/../bootstrap.php';

use Query\Tests\QueryTesterIblock;

$tester = new QueryTesterIblock(5);

$tester->assert(
    'range filter ><SORT',
    fn() => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID', 'SORT'])
        ->whereRaw([
            '><SORT' => [100, 300]
        ])
        ->get(),

    fn() => bxGet(
        [],
        ['><SORT' => [100, 300]],
        ['ID', 'SORT']
    )
);

$tester->assert(
    'comparison operators',
    fn() => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID'])
        ->whereRaw([
            '>SORT' => 100,
            '<=SORT' => 500
        ])
        ->get(),

    fn() => bxGet(
        [],
        [
            '>SORT' => 100,
            '<=SORT' => 500
        ],
        ['ID']
    )
);

$tester->assert(
    'NOT operator !ACTIVE',
    fn() => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID'])
        ->whereRaw([
            '!ACTIVE' => 'N'
        ])
        ->get(),

    fn() => bxGet(
        [],
        ['!ACTIVE' => 'N'],
        ['ID']
    )
);

$tester->assert(
    'LIKE %NAME%',
    fn() => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID', 'NAME'])
        ->whereRaw([
            '%NAME' => 'QB_TEST'
        ])
        ->get(),

    fn() => bxGet(
        [],
        ['%NAME' => 'QB_TEST'],
        ['ID', 'NAME']
    )
);

$tester->assert(
    'NULL handling',
    fn() => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID'])
        ->whereRaw([
            '=CODE' => null
        ])
        ->get(),

    fn() => bxGet(
        [],
        ['=CODE' => null],
        ['ID']
    )
);

$tester->assert(
    'boolean conversion',
    fn() => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID'])
        ->whereRaw([
            'ACTIVE' => true
        ])
        ->get(),

    fn() => bxGet(
        [],
        ['ACTIVE' => true],
        ['ID']
    )
);

$tester->assert(
    'mixed operators stability',
    fn() => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID'])
        ->whereRaw([
            'ACTIVE' => 'Y',
            '!CODE' => false,
            '>SORT' => 100,
            '<=SORT' => 500,
            '%NAME' => 'QB'
        ])
        ->get(),

    fn() => bxGet(
        [],
        [
            'ACTIVE' => 'Y',
            '!CODE' => false,
            '>SORT' => 100,
            '<=SORT' => 500,
            '%NAME' => 'QB'
        ],
        ['ID']
    )
);

$tester->assert(
    'IN empty array safety',
    fn() => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID'])
        ->whereRaw([
            'ID' => []
        ])
        ->get(),

    fn() => bxGet(
        [],
        ['ID' => []],
        ['ID']
    )
);

$tester->assert(
    'numeric string handling',
    fn() => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID'])
        ->whereRaw([
            'ID' => '33'
        ])
        ->get(),

    fn() => bxGet(
        [],
        ['ID' => '33'],
        ['ID']
    )
);
