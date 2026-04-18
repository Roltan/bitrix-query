<?php

require __DIR__ . '/../bootstrap.php';

use Query\Tests\QueryTesterIblock;

$tester = new QueryTesterIblock(5);

$tester->assert(
    'OR logic basic',
    fn() => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID'])
        ->whereRaw([
            'LOGIC' => 'OR',
            [
                'ID' => 33
            ],
            [
                'ID' => 90
            ]
        ])
        ->get(),

    fn() => bxGet(
        [],
        [
            'LOGIC' => 'OR',
            [
                'ID' => 33
            ],
            [
                'ID' => 90
            ]
        ],
        ['ID']
    )
);

$tester->assert(
    'AND inside OR group',
    fn() => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID'])
        ->whereRaw([
            'LOGIC' => 'OR',
            [
                'ACTIVE' => 'Y',
                'ID' => 33
            ],
            [
                'ACTIVE' => 'Y',
                'ID' => 90
            ]
        ])
        ->get(),

    fn() => bxGet(
        [],
        [
            'LOGIC' => 'OR',
            [
                'ACTIVE' => 'Y',
                'ID' => 33
            ],
            [
                'ACTIVE' => 'Y',
                'ID' => 90
            ]
        ],
        ['ID']
    )
);

$tester->assert(
    'nested groups OR + AND',
    fn() => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID'])
        ->whereRaw([
            'LOGIC' => 'AND',
            [
                'LOGIC' => 'OR',
                ['ID' => 33],
                ['ID' => 90]
            ],
            [
                'ACTIVE' => 'Y'
            ]
        ])
        ->get(),

    fn() => bxGet(
        [],
        [
            'LOGIC' => 'AND',
            [
                'LOGIC' => 'OR',
                ['ID' => 33],
                ['ID' => 90]
            ],
            [
                'ACTIVE' => 'Y'
            ]
        ],
        ['ID']
    )
);

$tester->assert(
    'empty group handling',
    fn() => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID'])
        ->whereRaw([
            'LOGIC' => 'OR'
        ])
        ->get(),

    fn() => bxGet(
        [],
        [
            'LOGIC' => 'OR'
        ],
        ['ID']
    )
);

$tester->assert(
    'scalar + group mix',
    fn() => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID'])
        ->whereRaw([
            'ACTIVE' => 'Y',
            'LOGIC' => 'OR',
            ['ID' => 33],
            ['ID' => 90]
        ])
        ->get(),

    fn() => bxGet(
        [],
        [
            'ACTIVE' => 'Y',
            'LOGIC' => 'OR',
            ['ID' => 33],
            ['ID' => 90]
        ],
        ['ID']
    )
);

$tester->assert(
    'deep nested OR/AND chain',
    fn() => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID'])
        ->whereRaw([
            'LOGIC' => 'AND',
            [
                'LOGIC' => 'OR',
                [
                    'LOGIC' => 'AND',
                    ['>SORT' => 100],
                    ['<SORT' => 500]
                ],
                [
                    'ID' => 33
                ]
            ],
            [
                'ACTIVE' => 'Y'
            ]
        ])
        ->get(),

    fn() => bxGet(
        [],
        [
            'LOGIC' => 'AND',
            [
                'LOGIC' => 'OR',
                [
                    'LOGIC' => 'AND',
                    ['>SORT' => 100],
                    ['<SORT' => 500]
                ],
                [
                    'ID' => 33
                ]
            ],
            [
                'ACTIVE' => 'Y'
            ]
        ],
        ['ID']
    )
);

$tester->assert(
    'OR with LIKE + RANGE',
    fn() => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID'])
        ->whereRaw([[
            'LOGIC' => 'OR',
            ['%NAME' => 'QB_TEST'],
            ['><SORT' => [100, 300]]
        ]])
        ->get(),

    fn() => bxGet(
        [],
        [
            'IBLOKC_ID' => 5,
            [
                'LOGIC' => 'OR',
                ['%NAME' => 'QB_TEST'],
                ['><SORT' => [100, 300]]
            ]
        ],
        ['ID']
    )
);

$tester->assert(
    'OR collapse prevention',
    fn() => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID'])
        ->whereRaw([
            'LOGIC' => 'OR',
            'ACTIVE' => 'Y',
            'ID' => 33
        ])
        ->get(),

    fn() => bxGet(
        [],
        [
            'LOGIC' => 'OR',
            'ACTIVE' => 'Y',
            'ID' => 33
        ],
        ['ID']
    )
);