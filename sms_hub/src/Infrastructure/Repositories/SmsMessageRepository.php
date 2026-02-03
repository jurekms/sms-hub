<?php
namespace Infrastructure\Repositories;

use PDO;

final class SmsMessageRepository
{
    public function __construct(private PDO $pdo) {}

    public function create(
        int $batchId,
        string $phone,
        string $message,
        int $parts
    ): int {
        $stmt = $this->pdo->prepare(
            "INSERT INTO sms_messages
             (batch_id, phone, message, parts, status)
             VALUES (:batch, :phone, :message, :parts, 'queued')"
        );

        $stmt->execute([
            'batch'   => $batchId,
            'phone'   => $phone,
            'message' => $message,
            'parts'   => $parts,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function markQueued(int $id, int $outboxId): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE sms_messages
             SET smsd_outbox_id = :outbox
             WHERE id = :id"
        );

        $stmt->execute([
            'outbox' => $outboxId,
            'id'     => $id,
        ]);
    }
}
