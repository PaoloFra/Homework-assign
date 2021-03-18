<?php

declare(strict_types=1);

namespace Homework\CommissionTask\Service;

use \BenMajor\ExchangeRatesAPI\ExchangeRatesAPI;

class CommissionFee
{
    /**
     * @var string[]
     */
    private static $currencyAllowed = [
        'EUR',
        'USD',
        'JPY',
    ];

    /**
     * @var int
     */
    private static $weeklyAmount = 1000;

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

    public function process($operations)
    {
        $this->operations = $operations;

        if (count($this->currencyRates) === 0) {
            $this->fillExchangeRates();
        }

        foreach ($operations as $operation) {
            list($date, $clientID, $clientType, $operationType, $amount, $currency) = $operation;
            if (in_array($currency, self::$currencyAllowed, true)) {
                $beginningOfWeek = date('Y-m-d', strtotime('Monday this week', strtotime($date)));
                $operationTypeMethod = $operationType . 'Charge';
                // $operationType: deposit or withdraw
                // processed with depositCharge or withdrawCharge method
                $this->fees[] = $this->$operationTypeMethod($beginningOfWeek, $clientID, $clientType, $amount, $currency);
            }
        }
        return $this->fees;
    }

    private function fillExchangeRates()
    {
        foreach (self::$currencyAllowed as $item) {
            if ($item === 'EUR') {
                $this->currencyRates[$item] = 1;
            } else {
                $lookup = new ExchangeRatesAPI();
                $this->currencyRates[$item] = $lookup->convert($item, 1);
            }
        }
    }

    private function depositCharge($date, $clientID, $clientType, $amount, $currency)
    {
        $decimalPoint = (int)strpos(strrev($amount), '.', 0);
        $powerCoeff = 10 ** ($decimalPoint - 2);
        $fee = ceil($amount * 0.03 * $powerCoeff) / $powerCoeff;
        return bcmul((string)$fee, '0.01', $decimalPoint);
    }

    /**
     * @param $date
     * @param $clientID
     * @param $clientType
     * @param $amount
     * @param $currency
     * @return string
     */
    private function withdrawCharge($date, $clientID, $clientType, $amount, $currency)
    {
        $decimalPoint = (int)strpos(strrev($amount), '.', 0);
        $powerCoeff = 10 ** ($decimalPoint - 2);

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
                    if ($amountUsedInEuro < self::$weeklyAmount)
                    {
                        $fee = 0;
                    } else {
                        $fee = ceil((($amountUsedInEuro - self::$weeklyAmount) * $this->currencyRates[$currency]) * 0.3 * $powerCoeff) / $powerCoeff;
                        $this->withdrawBalance[$clientID][$date]['itemNumber'] = 3;
                    }
                }

                break;
        }

        return bcmul((string)$fee, '0.01', $decimalPoint);
    }
}