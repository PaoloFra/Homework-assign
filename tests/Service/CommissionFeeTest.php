<?php

declare(strict_types=1);

namespace Homework\CommissionTask\Tests\Service;

use PHPUnit\Framework\TestCase;
use Homework\CommissionTask\Service\CommissionFee;

class CommissionFeeTest extends TestCase
{
    /**
     * @var CommissionFee
     */
    private $commissionFee;

    private $fees = [
        '0.60',
        '3.00',
        '0.00',
        '0.06',
        '1.50',
        '0',
        '0.70',
        '0.30',
        '0.30',
        '3.00',
        '0.00',
        '0.00',
        '8612',
    ];

    private $operations = [
        [
            '2014-12-31',
            '4',
            'private',
            'withdraw',
            '1200.00',
            'EUR',
        ],
        [
            '2015-01-01',
            '4',
            'private',
            'withdraw',
            '1000.00',
            'EUR',
        ],
        [
            '2016-01-05',
            '4',
            'private',
            'withdraw',
            '1000.00',
            'EUR',
        ],
        [
            '2016-01-05',
            '1',
            'private',
            'deposit',
            '200.00',
            'EUR',
        ],
        [
            '2016-01-06',
            '2',
            'business',
            'withdraw',
            '300.00',
            'EUR',
        ],
        [
            '2016-01-06',
            '1',
            'private',
            'withdraw',
            '30000',
            'JPY',
        ],
        [
            '2016-01-07',
            '1',
            'private',
            'withdraw',
            '1000.00',
            'EUR',
        ],
        [
            '2016-01-07',
            '1',
            'private',
            'withdraw',
            '100.00',
            'USD',
        ],
        [
            '2016-01-10',
            '1',
            'private',
            'withdraw',
            '100.00',
            'EUR',
        ],
        [
            '2016-01-10',
            '2',
            'business',
            'deposit',
            '10000.00',
            'EUR',
        ],
        [
            '2016-01-10',
            '3',
            'private',
            'withdraw',
            '1000.00',
            'EUR',
        ],
        [
            '2016-02-15',
            '1',
            'private',
            'withdraw',
            '300.00',
            'EUR',
        ],
        [
            '2016-02-19',
            '5',
            'private',
            'withdraw',
            '3000000',
            'JPY',
        ],
    ];

    public function setUp(): void
    {
        $this->commissionFee = new CommissionFee([
            'EUR' => 1,
            'USD' => 1.1497,
            'JPY' => 129.53
        ]);
    }

    /**
     * @param string $leftOperand
     * @param string $rightOperand
     * @param string $expectation
     *
     * @dataProvider dataProviderForAddTesting
     */
    public function testAdd(array $leftOperand, array $expectation)
    {
        $this->assertEquals(
            $expectation,
            $this->commissionFee->process($leftOperand)
        );
    }

    public function dataProviderForAddTesting(): array
    {
        return [
            'fees charged' => [$this->operations, $this->fees],
        ];
    }
}
