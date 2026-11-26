<?php

namespace Tests\Unit\Domain\Shared\Phone;

use App\Domain\Shared\Phone\PhoneNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PhoneNumberTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: ?string}>
     */
    public static function numbers(): array
    {
        return [
            'local with spaces' => ['0803 123 4567', '+2348031234567'],
            'local without spaces' => ['08031234567', '+2348031234567'],
            'international with plus' => ['+234 803 123 4567', '+2348031234567'],
            'international without plus' => ['2348031234567', '+2348031234567'],
            'with dashes and brackets' => ['(0806) 000-1917', '+2348060001917'],
            '070 prefix' => ['07031234567', '+2347031234567'],
            '090 prefix' => ['09061234567', '+2349061234567'],
            'another country' => ['+44 20 7946 0958', '+442079460958'],
            'too short' => ['0803 12', null],
            'landline style is not a mobile' => ['01 234 5678', null],
            'letters' => ['0803-CALL-ME', null],
            'empty' => ['  ', null],
        ];
    }

    #[DataProvider('numbers')]
    public function test_numbers_are_normalised_to_e164(string $input, ?string $expected): void
    {
        $this->assertSame($expected, PhoneNumber::normalise($input));
    }

    public function test_an_email_does_not_look_like_a_phone(): void
    {
        $this->assertFalse(PhoneNumber::looksLikePhone('kemi@adunni.test'));
        $this->assertTrue(PhoneNumber::looksLikePhone('+234 803 123 4567'));
    }
}
