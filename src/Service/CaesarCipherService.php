<?php
namespace App\Service;

class CaesarCipherService
{
    public function encrypt($text, $shift): string
    {
        // 1) must be a string of letters only
        if (!is_string($text) || !preg_match('/^[A-Za-z]+$/', $text)) {
            throw new \InvalidArgumentException('Text must be a non-empty alphabetic string A–Z.');
        }

        // 2) shift must be an integer
        if (!is_int($shift)) {
            throw new \InvalidArgumentException('Shift must be an integer.');
        }

        // 3) shift must be between 0 and 25
        if ($shift < 0 || $shift > 25) {
            throw new \InvalidArgumentException('Shift must be between 0 and 25.');
        }

        $result = '';
        foreach (str_split($text) as $char) {
            // we know char is A–Z or a–z because of the preg above
            $isUpper = ctype_upper($char);
            $offset  = $isUpper ? ord('A') : ord('a');
            $result .= chr((ord($char) - $offset + $shift) % 26 + $offset);
        }
        return $result;
    }

    public function decrypt($text, $shift): string
    {
        // same guards apply
        if (!is_string($text) || !preg_match('/^[A-Za-z]+$/', $text)) {
            throw new \InvalidArgumentException('Text must be a non-empty alphabetic string A–Z.');
        }
        if (!is_int($shift)) {
            throw new \InvalidArgumentException('Shift must be an integer.');
        }
        if ($shift < 0 || $shift > 25) {
            throw new \InvalidArgumentException('Shift must be between 0 and 25.');
        }

        // decrypt by shifting backwards
        return $this->encrypt($text, (26 - $shift) % 26);
    }
}