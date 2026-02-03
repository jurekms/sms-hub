<?php
namespace Infrastructure\Repositories;

use PDO;

final class ApiClientRepository
{
    public function __construct(private PDO $pdo) {}

    public function findActiveByApiKey(string $apiKey): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, name, daily_limit
             FROM api_clients
             WHERE api_key = :key
               AND is_active = 1'
        );

        $stmt->execute(['key' => $apiKey]);

        $client = $stmt->fetch();

        return $client ?: null;
    }
}
