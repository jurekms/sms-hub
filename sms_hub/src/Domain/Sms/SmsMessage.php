<?php
namespace Domain\Sms;

final class SmsMessage
{
    public readonly string $phone;
    public readonly string $text;

    public function __construct(string $phone, string $text)
    {
        $phone = trim($phone);
        $text  = trim($text);

        if ($phone === '') {
            throw new \InvalidArgumentException('Phone number is required');
        }

        if ($text === '') {
            throw new \InvalidArgumentException('SMS text is required');
        }

        $this->phone = $phone;
        $this->text  = $text;
    }
}
