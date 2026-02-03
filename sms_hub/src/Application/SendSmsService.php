<?php
namespace Application;

use Domain\Sms\SmsMessage;
use Domain\Sms\EncoderInterface;
use Infrastructure\Repositories\SmsdRepository;
use Infrastructure\Repositories\SmsBatchRepository;
use Infrastructure\Repositories\SmsMessageRepository;

final class SendSmsService
{
    public function __construct(
        private EncoderInterface $encoder,
        private SmsdRepository $smsd,
        private SmsBatchRepository $batches,
        private SmsMessageRepository $messages,
        private string $creatorId
    ) {}

    public function send(
        SmsMessage $sms,
        int $apiClientId
    ): int {
        $parts = $this->encoder->split($sms->text);

        // 1️⃣ batch
        $batchId = $this->batches->create(
            $apiClientId,
            $sms->text,
            1
        );

        // 2️⃣ historia
        $messageId = $this->messages->create(
            $batchId,
            $sms->phone,
            $sms->text,
            count($parts)
        );

        // 3️⃣ wysyłka
        $hexParts = array_map(
            fn($p) => $this->encoder->encode($p),
            $parts
        );

        $outboxId = $this->smsd->insertMultipart(
            $sms->phone,
            $parts,
            $hexParts,
            $this->creatorId
        );

        // 4️⃣ powiązanie z smsd
        $this->messages->markQueued($messageId, $outboxId);

        return $messageId;
    }
}
