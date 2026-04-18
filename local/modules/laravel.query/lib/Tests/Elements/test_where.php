<?php

require __DIR__ . '/../bootstrap.php';

use Query\Tests\QueryTesterIblock;

$tester = new QueryTesterIblock(5);

$tester->assert(
    'orGroup AND inside OR',
    fn() => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID'])
        ->orGroup(function ($q) {
            $q->where('ACTIVE', 'Y')
                ->where('SORT', '>', 100);
        })
        ->get(),

    fn() => bxGet(
        [],
        [
            'LOGIC' => 'OR',
            [
                'ACTIVE' => 'Y',
                '>SORT' => 100,
            ]
        ],
        ['ID']
    )
);

$tester->assert(
    'orWhereProperty comparison',
    fn() => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID'])
        ->orWhereProperty('PRICE', '>=', 1000)
        ->get(),

    fn() => bxGet(
        [],
        [
            'LOGIC' => 'OR',
            '>=PROPERTY_PRICE' => 1000,
        ],
        ['ID']
    )
);

$tester->assert(
    'whereIn array values',
    fn() => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID'])
        ->whereIn('ID', [1, 2, 3])
        ->get(),

    fn() => bxGet(
        [],
        [
            'ID' => [1, 2, 3],
        ],
        ['ID']
    )
);

$tester->assert(
    'whereNotIn',
    fn() => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID'])
        ->whereNotIn('ID', [10, 20])
        ->get(),

    fn() => bxGet(
        [],
        ['!ID' => [10, 20],],
        ['ID']
    )
);

$tester->assert(
    'whereBetween PRICE',
    fn() => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID'])
        ->whereBetween('PRICE', 100, 500)
        ->get(),

    fn() => bxGet(
        [],
        [
            '>=PRICE' => 100,
            '<=PRICE' => 500,
        ],
        ['ID']
    )
);

$tester->assert(
    'whereNot ACTIVE',
    fn() => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID'])
        ->whereNot('ACTIVE', 'Y')
        ->get(),

    fn() => bxGet(
        [],
        [
            '!ACTIVE' => 'Y',
        ],
        ['ID']
    )
);

$tester->assert(
    'whereNull DATE',
    fn() => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID'])
        ->whereNull('DATE_ACTIVE_FROM')
        ->get(),

    fn() => bxGet(
        [],
        ['DATE_ACTIVE_FROM' => false,],
        ['ID']
    )
);

$tester->assert(
    'whereLike NAME',
    fn() => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID'])
        ->whereLike('NAME', 'iphone')
        ->get(),

    fn() => bxGet(
        [],
        [
            '%NAME' => 'iphone',
        ],
        ['ID']
    )
);

$tester->assert(
    'AND stacking iblock + active + section',
    fn() => \Query\ElementQuery::query()
        ->iblock(5)
        ->active()
        ->section(10)
        ->select(['ID'])
        ->get(),

    fn() => bxGet(
        [],
        [
            'IBLOCK_ID' => 5,
            'ACTIVE' => 'Y',
            'SECTION_ID' => 10,
        ],
        ['ID']
    )
);

$tester->assert(
    'whereProperty >= normalization',
    fn() => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID'])
        ->whereProperty('price', '>=', 100)
        ->get(),

    fn() => bxGet(
        [],
        [
            '>=PROPERTY_PRICE' => 100,
        ],
        ['ID']
    )
);