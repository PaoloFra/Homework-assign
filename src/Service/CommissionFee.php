<?php

declare(strict_types=1);

namespace Homework\CommissionTask\Service;

use \BenMajor\ExchangeRatesAPI\ExchangeRatesAPI;

class CommissionFee
{
    /**
     * @var string[]|array
     */
    private $currencyAllowed = [
        'EUR',
        'USD',
        'JPY',
    ];

    /**
     * @var int
     */
    private $weeklyAmount = 1000;

    /**
     * @var array
     */
    private $currencyRates;

    /**
     * @var array
     */
    private $operations;

    /**
     * @var array
     */
    private $withdrawBalance;

    /**
     * @var array
     */
    private $fees;

    public function __construct(array $currencyRates = [])
    {
        $this->currencyRates = $currencyRates;
    }

    /**
     * @param array $operations
     * @return array
     */
    public function process(array $operations)
    {
        $this->operations = $operations;

        // fill Currency Exchange Rates from api.exchangeratesapi.io if not provided
        if (count($this->currencyRates) === 0) {
            $this->fillExchangeRates();
        }

        foreach ($operations as $operation) {
            list($date, $clientID, $clientType, $operationType, $amount, $currency) = $operation;

            if (in_array($currency, $this->currencyAllowed, true)) {
                $beginningOfWeek = date('Y-m-d', strtotime('Monday this week', strtotime($date)));
                $operationTypeMethod = $operationType . 'Charge';
                // $operationType: 'deposit' or 'withdraw'
                // processed with depositCharge or withdrawCharge method
                $this->fees[] = $this->$operationTypeMethod($beginningOfWeek, $clientID, $clientType, $amount, $currency);
            } else {
                $this->fees[] = 'N/A';
            }
        }
        return $this->fees;
    }

    /**
     * @return void
     * @throws \BenMajor\ExchangeRatesAPI\Exception
     */
    private function fillExchangeRates()
    {
        foreach ($this->currencyAllowed as $item) {
            if ($item === 'EUR') {
                $this->currencyRates[$item] = 1;
            } else {
                $lookup = new ExchangeRatesAPI();
                $this->currencyRates[$item] = $lookup->convert($item, 1);
            }
        }
    }

    /**
     * @param string $date
     * @param string $clientID
     * @param string $clientType
     * @param string $amount
     * @param string $currency
     *
     * @return string
     */
    private function depositCharge($date, $clientID, $clientType, $amount, $currency)
    {
        $decimalPoint = (int)strpos(strrev($amount), '.', 0);
        $powerCoeff = 10 ** ($decimalPoint - 2);
        $fee = ceil($amount * 0.03 * $powerCoeff) / $powerCoeff;
        return bcmul((string)$fee, '0.01', $decimalPoint);
    }

    /**
     * @param string $date
     * @param string $clientID
     * @param string $clientType
     * @param string $amount
     * @param string $currency
     *
     * @return string
     */
    private function withdrawCharge($date, $clientID, $clientType, $amount, $currency)
    {
        $decimalPoint = (int)strpos(strrev($amount), '.', 0);
        $powerCoeff = 10 ** ($decimalPoint - 2);
        // for rounded up to currency's decimal places

        switch ($clientType) {
            case 'business':
                $fee = ceil($amount * 0.5 * $powerCoeff) / $powerCoeff;
                break;
            case 'private':
                    $this->withdrawBalance[$clientID][$date]['itemNumber'] += 1;
                    $this->withdrawBalance[$clientID][$date]['amountUsedInEuro'] += $amount / $this->currencyRates[$currency];

            $amountUsedInEuro = $this->withdrawBalance[$clientID][$date]['amountUsedInEuro'];

                if ($this->withdrawBalance[$clientID][$date]['itemNumber'] > 3) {
                    $fee = ceil($amount * 0.3 * $powerCoeff) / $powerCoeff;
                } else {
                    if ($amountUsedInEuro < $this->weeklyAmount)
                    {
                        $fee = 0;
                    } else {
                        $fee = ceil((($amountUsedInEuro - $this->weeklyAmount) * $this->currencyRates[$currency]) * 0.3 * $powerCoeff) / $powerCoeff;
                        $this->withdrawBalance[$clientID][$date]['itemNumber'] = 3;
                    }
                }

                break;
        }

        return bcmul((string)$fee, '0.01', $decimalPoint);
    }
}