<?php
namespace Infrastructure\Repositories;

use PDO;
use Domain\Sms\Udh;

final class SmsdRepository
{
    public function __construct(private PDO $pdo) {}

    public function insertMultipart(
        string $phone,
        array $decodedParts,
        array $hexParts,
        string $creatorId
    ): int {
        $ref   = random_int(0, 255);
        $total = count($decodedParts);

        $this->pdo->beginTransaction();

        try {
            // OUTBOX (pierwsza część)
            $stmt = $this->pdo->prepare(
                "INSERT INTO smsd.outbox
                 (CreatorID, MultiPart, DestinationNumber, UDH, Text, TextDecoded, Coding, Class, RelativeValidity)
                 VALUES (:creator, 'true', :phone, :udh, :text, :decoded, 'Default_No_Compression', -1, 255)"
            );

            $stmt->execute([
                'creator' => $creatorId,
                'phone'   => $phone,
                'udh'     => $this->udh($ref, $total, 1),
                'text'    => $hexParts[0],
                'decoded' => $decodedParts[0],
            ]);

            $outboxId = (int) $this->pdo->lastInsertId();

            // MULTIPART
            if ($total > 1) {
                $stmt = $this->pdo->prepare(
                    "INSERT INTO smsd.outbox_multipart
                     (ID, SequencePosition, UDH, Text, TextDecoded, Coding, Class)
                     VALUES (:id, :seq, :udh, :text, :decoded, 'Default_No_Compression', -1)"
                );

                for ($i = 1; $i < $total; $i++) {
                    $stmt->execute([
                        'id'      => $outboxId,
                        'seq'     => $i + 1,
                        'udh'     => $this->udh($ref, $total, $i + 1),
                        'text'    => $hexParts[$i],
                        'decoded' => $decodedParts[$i],
                    ]);
                }
            }

            $this->pdo->commit();
            return $outboxId;

        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    private function udh(int $ref, int $total, int $part): string
    {
        return sprintf('050003%02X%02X%02X', $ref, $total, $part);
    }
}
