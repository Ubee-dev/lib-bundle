<?php

namespace UbeeDev\LibBundle\Traits;

use Money\Currencies\ISOCurrencies;
use Money\Formatter\IntlMoneyFormatter;
use Money\Money;

trait MoneyTrait
{

    public function formatMoney(Money $money): string
    {
        $currencies = new ISOCurrencies();

        $numberFormatter = new \NumberFormatter('FR_fr', \NumberFormatter::CURRENCY);
        $moneyFormatter = new IntlMoneyFormatter($numberFormatter, $currencies);

        return $this->cleanString($moneyFormatter->format($money));
    }

    public function formatMoneyToFloat(Money $money): float|int
    {
        return $money->getAmount() / 100;
    }

    private function cleanString(string $string): string
    {
        return preg_replace('/\s/u', ' ', $string);
    }
}
