<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Refuses passwords that are among the most guessed (SEC-019), including
 * ones built from the salon's own words. Checked locally: passwords are
 * never sent to an outside service.
 */
class NotCommonPassword implements ValidationRule
{
    /**
     * @var list<string>
     */
    private const COMMON = [
        '12345678', '123456789', '1234567890', '87654321', '11111111', '00000000', '12341234', '11223344',
        '12121212', '88888888', '99999999', '55555555', '66666666', '77777777', '22222222', '33333333',
        'password', 'password1', 'password12', 'password123', 'passw0rd', 'p@ssw0rd', 'p@ssword', 'pa$$word',
        'qwerty12', 'qwerty123', 'qwertyuiop', 'asdfghjkl', 'zxcvbnm1', '1q2w3e4r', '1qaz2wsx', 'abcd1234',
        'abc12345', 'abcdefgh', 'iloveyou', 'letmein1', 'welcome1', 'welcome123', 'admin123', 'administrator',
        'sunshine', 'princess', 'football', 'baseball', 'superman', 'trustno1', 'whatever', 'computer',
        'jesus123', 'godisgood', 'blessing', 'blessed1', 'nigeria1', 'nigeria123', 'lagos123', 'salon123',
        'beauty123', 'changeme', 'default1', 'temp1234', 'test1234', 'testing1', 'secret12', 'master12',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (in_array(mb_strtolower((string) $value), self::COMMON, true)) {
            $fail('This password is too easy to guess. Choose another.');
        }
    }
}
