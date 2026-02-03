<?php
namespace Domain\Sms;

final class Ucs2Encoder implements EncoderInterface
{
    public function partLength(): int
    {
        // 153 znaki przy multipart UCS-2
        return 153;
    }

    public function split(string $text): array
    {
        return mb_str_split($text, $this->partLength(), 'UTF-8');
    }

    public function encode(string $text): string
    {
        $ucs2 = mb_convert_encoding($text, 'UCS-2BE', 'UTF-8');
        return strtoupper(bin2hex($ucs2));
    }
}
