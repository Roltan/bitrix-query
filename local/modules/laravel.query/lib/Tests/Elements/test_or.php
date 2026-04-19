<?php

require __DIR__ . '/../bootstrap.php';

use Query\Tests\QueryTesterIblock;

$tester = new QueryTesterIblock(5);

$tester->assert(
    'orWhere creates OR group',
    fn($ids) => \Query\ElementQuery::query()
        ->iblock(5)
        ->orWhere('ID', $ids[0])
        ->select(['ID'])
        ->get(),

    fn($ids) => bxGet(
        [],
        [
            [
                'LOGIC' => 'OR',
                ['IBLOCK_ID' => 5],
                ['ID' => $ids[0]]
            ],
        ],
        ['ID']
    )
);

$tester->assert(
    'multiple orWhere creates multiple OR groups',
    fn($ids) => \Query\ElementQuery::query()
        ->iblock(5)
        ->orWhere('ID', $ids[0])
        ->orWhere('ID', $ids[1])
        ->select(['ID'])
        ->get(),

    fn($ids) => bxGet(
        [],
        [
            'IBLOCK_ID' => 5,
            [
                'LOGIC' => 'OR',
                ['ACTIVE' => 'Y'],
                ['ID' => $ids[0]],
                ['ID' => $ids[1]]
            ]
        ],
        ['ID']
    )
);

$tester->assert(
    'orGroup isolates filter context',
    fn($ids) => \Query\ElementQuery::query()
        ->iblock(5)
        ->orGroup(function ($q) {
            $q->where('NAME', 'A')
                ->where('CODE', 'B');
        })
        ->select(['ID'])
        ->get(),

    fn($ids) => bxGet(
        [],
        [
            [
                'LOGIC' => 'OR',
                ['NAME' => 'A'],
                ['CODE' => 'B']
            ],
            'IBLOCK_ID' => 5
        ],
        ['ID']
    )
);

$tester->assert(
    'AND + OR mix',
    fn($ids) => \Query\ElementQuery::query()
        ->iblock(5)
        ->where('ACTIVE', 'Y')
        ->orWhere('ID', $ids[0])
        ->select(['ID'])
        ->get(),

    fn($ids) => bxGet(
        [],
        [
            [
                'LOGIC' => 'OR',
                ['ACTIVE' => 'Y'],
                ['ID' => $ids[0]]
            ],
            'IBLOCK_ID' => 5
        ],
        ['ID']
    )
);

$tester->assert(
    'orRaw passes structure as-is',
    fn($ids) => \Query\ElementQuery::query()
        ->iblock(5)
        ->orRaw([
            ['%NAME' => 'A'],
            ['%CODE' => 'B']
        ])
        ->select(['ID'])
        ->get(),

    fn($ids) => bxGet(
        [],
        [
            [
                'LOGIC' => 'OR',
                ['%NAME' => 'A'],
                ['%CODE' => 'B']
            ],
            'IBLOCK_ID' => 5
        ],
        ['ID']
    )
);
