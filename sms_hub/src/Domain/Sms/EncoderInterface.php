<?php
namespace Domain\Sms;

interface EncoderInterface
{
    /**
     * Dzieli tekst na części SMS
     * @return string[]
     */
    public function split(string $text): array;

    /**
     * Koduje jedną część do HEX (pod smsd)
     */
    public function encode(string $text): string;

    /**
     * Ile znaków w jednej części
     */
    public function partLength(): int;
}
