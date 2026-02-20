<?php

namespace UbeeDev\LibBundle\Traits;

use Money\Currencies\ISOCurrencies;
use Money\Formatter\IntlMoneyFormatter;
use Money\Money;

trait MoneyTrait
{

    /**
     * @param Money $money
     * @return string
     */
    public function formatMoney(Money $money)
    {
        $currencies = new ISOCurrencies();

        $numberFormatter = new \NumberFormatter('FR_fr', \NumberFormatter::CURRENCY);
        $moneyFormatter = new IntlMoneyFormatter($numberFormatter, $currencies);

        return $this->cleanString($moneyFormatter->format($money));
    }

    /**
     * @param Money $money
     * @return float|int
     */
    public function formatMoneyToFloat(Money $money)
    {
        return $money->getAmount() / 100;
    }


    private function cleanString($string)
    {
        return preg_replace('/\s/u', ' ', $string);
    }
}
