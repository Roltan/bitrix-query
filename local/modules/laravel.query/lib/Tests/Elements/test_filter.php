<?php

require __DIR__ . '/../bootstrap.php';

use Query\Tests\QueryTesterIblock;

$tester = new QueryTesterIblock(5);

$tester->assert(
    'basic AND filters',
    fn($ids) => \Query\ElementQuery::query()
        ->iblock(5)
        ->active()
        ->where('NAME', 'TEST')
        ->select(['ID'])
        ->get(),

    fn($ids) => bxGet(
        [],
        [
            'IBLOCK_ID' => 5,
            'ACTIVE' => 'Y',
            'NAME' => 'TEST'
        ],
        ['ID']
    )
);

$tester->assert(
    'where with operator >',
    fn($ids) => \Query\ElementQuery::query()
        ->iblock(5)
        ->where('SORT', '>', 100)
        ->select(['ID'])
        ->get(),

    fn($ids) => bxGet(
        [],
        ['>SORT' => 100, 'IBLOCK_ID' => 5],
        ['ID']
    )
);

$tester->assert(
    'whereNot works',
    fn($ids) => \Query\ElementQuery::query()
        ->iblock(5)
        ->whereNot('ACTIVE', 'Y')
        ->select(['ID'])
        ->get(),

    fn($ids) => bxGet(
        [],
        [
            '!ACTIVE' => 'Y',
            'IBLOCK_ID' => 5
        ],
        ['ID']
    )
);

$tester->assert(
    'whereIn works',
    fn($ids) => \Query\ElementQuery::query()
        ->iblock(5)
        ->whereIn('ID', [1, 2, 3])
        ->select(['ID'])
        ->get(),

    fn($ids) => bxGet(
        [],
        [
            'ID' => [1, 2, 3],
            'IBLOCK_ID' => 5
        ],
        ['ID']
    )
);

$tester->assert(
    'whereBetween expands into >= and <=',
    fn($ids) => \Query\ElementQuery::query()
        ->iblock(5)
        ->whereBetween('SORT', 100, 200)
        ->select(['ID'])
        ->get(),

    fn($ids) => bxGet(
        [],
        [
            '>=SORT' => 100,
            '<=SORT' => 200,
            'IBLOCK_ID' => 5
        ],
        ['ID']
    )
);

$tester->assert(
    'whereLike uses % operator',
    fn($ids) => \Query\ElementQuery::query()
        ->iblock(5)
        ->whereLike('NAME', 'iphone')
        ->select(['ID'])
        ->get(),

    fn($ids) => bxGet(
        [],
        [
            '%NAME' => 'iphone',
            'IBLOCK_ID' => 5
        ],
        ['ID']
    )
);

$tester->assert(
    'iblock + active shortcut consistency',
    fn($ids) => \Query\ElementQuery::query()
        ->iblock(5)
        ->active()
        ->section(10)
        ->select(['ID'])
        ->get(),

    fn($ids) => bxGet(
        [],
        [
            'IBLOCK_ID' => 5,
            'ACTIVE' => 'Y',
            'SECTION_ID' => 10
        ],
        ['ID']
    )
);

$tester->assert(
    'whereRaw merges correctly into filter array',
    fn($ids) => \Query\ElementQuery::query()
        ->iblock(5)
        ->whereRaw([
            '>SORT' => 100,
            'ACTIVE' => 'Y'
        ])
        ->select(['ID'])
        ->get(),

    fn($ids) => bxGet(
        [],
        [
            '>SORT' => 100,
            'ACTIVE' => 'Y',
            'IBLOCK_ID' => 5
        ],
        ['ID']
    )
);