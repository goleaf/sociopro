<?php

namespace Tests\Unit;

use App\Support\Money\Money;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MoneyTest extends TestCase
{
    /**
     * @return array<string, array{mixed, int}>
     */
    public static function validMoneyAmounts(): array
    {
        return [
            'integer' => [19, 1900],
            'whole string' => ['19', 1900],
            'one decimal place' => ['19.5', 1950],
            'two decimal places' => ['19.50', 1950],
            'smallest minor unit' => ['0.01', 1],
            'maximum supported decimal' => ['9999999999.99', 999999999999],
        ];
    }

    #[DataProvider('validMoneyAmounts')]
    public function test_it_converts_decimal_money_to_minor_units(mixed $amount, int $expectedMinorUnits): void
    {
        $this->assertSame($expectedMinorUnits, Money::toMinorUnits($amount));
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function invalidMoneyAmounts(): array
    {
        return [
            'negative' => ['-1.00'],
            'text' => ['free'],
            'too many decimals' => ['19.999'],
            'too many major digits' => ['10000000000.00'],
            'blank' => [''],
            'null' => [null],
        ];
    }

    #[DataProvider('invalidMoneyAmounts')]
    public function test_it_rejects_invalid_money_amounts(mixed $amount): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::toMinorUnits($amount);
    }
}
