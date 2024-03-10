<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Doctrine;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\Mapping\MappingException;
use Tests\Shared\Infrastructure\Shared\Doctrine\MySqlDatabaseCleaner;

use function Lambdish\Phunctional\apply;
use function Lambdish\Phunctional\each;

final readonly class DatabaseConnections
{
    private array $connections;

    public function __construct(iterable $connections)
    {
        if (is_array($connections)) {
            $this->connections = $connections;
        } else {
            $this->connections = iterator_to_array($connections);
        }
    }

    /**
     * @throws MappingException
     */
    public function clear(): void
    {
        each(fn (EntityManager $entityManager) => $entityManager->clear(), $this->connections);
    }

    public function truncate(): void
    {
        apply(new MySqlDatabaseCleaner(), array_values($this->connections));
    }
}
