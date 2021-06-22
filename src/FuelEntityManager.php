<?php

declare(strict_types=1);

namespace Stwarog\UowFuel;

use Exception;
use Fuel\Core\DB;
use Orm\Model;
use Stwarog\Uow\EntityManager;
use Stwarog\Uow\EntityManagerInterface;
use Stwarog\Uow\UnitOfWork\UnitOfWork;

final class FuelEntityManager extends EntityManager implements EntityManagerInterface
{
    /**
     * Initializes new instance (Forge in Fuel's nomenclature).
     *
     * @param DB                    $db
     * @param array<string, string> $config
     *
     * @return self
     */
    public static function forge(DB $db, array $config = []): self
    {
        return new self(new FuelDBAdapter($db), new UnitOfWork(), $config);
    }

    /**
     * @param Model $orm
     * @param bool  $flush
     *
     * @throws Exception
     */
    public function save(Model $orm, bool $flush = false): void
    {
        $this->persist(new FuelModelAdapter($orm));
        if ($flush) {
            $this->flush();
        }
    }

    public function delete(Model $orm): void
    {
        $this->remove(new FuelModelAdapter($orm));
    }
}
