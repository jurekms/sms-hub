<?php
namespace Infrastructure\Repositories;

use PDO;

final class SmsBatchRepository
{
    public function __construct(private PDO $pdo) {}

    public function create(int $apiClientId, string $message, int $recipients): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO sms_batches
             (api_client_id, message, total_recipients)
             VALUES (:client, :message, :total)"
        );

        $stmt->execute([
            'client'  => $apiClientId,
            'message' => $message,
            'total'   => $recipients,
        ]);

        return (int)$this->pdo->lastInsertId();
    }
}
