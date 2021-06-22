<?php

declare(strict_types=1);

namespace Stwarog\UowFuel;

use Closure;
use InvalidArgumentException;
use Orm\Model;
use Stwarog\Uow\DBConnectionInterface;
use Stwarog\Uow\EntityInterface;
use Stwarog\Uow\IdGenerators\AutoIncrementIdStrategy;
use Stwarog\Uow\IdGenerators\HasIdStrategy;
use Stwarog\Uow\IdGenerators\IdGenerationStrategyInterface;
use Stwarog\Uow\RelationBag;
use Stwarog\Uow\Relations\BelongsTo;
use Stwarog\Uow\Relations\HasMany;
use Stwarog\Uow\Relations\HasOne;
use Stwarog\Uow\Relations\ManyToMany;
use Stwarog\Uow\Relations\RelationInterface;

final class FuelModelAdapter implements EntityInterface
{
    /** @var Model */
    private $model;
    /** @var RelationBag<RelationInterface> */
    private $relations;
    /** @var string */
    private $objectHash;
    /** @var string */
    private $idKey = '';
    /** @var array<Closure> */
    private $closures = [];

    public function __construct(Model $model)
    {
        $this->model = $model;
        $this->objectHash = spl_object_hash($model);
        $assoc = array_keys($this->model->get_pk_assoc());
        $this->idKey = array_values($assoc)[0];
    }

    public function isDirty(): bool
    {
        return false === empty($this->getDifferences());
    }

    /**
     * @return array<int, string>
     */
    private function getDifferences(): array
    {
        $data = _get($this->model, '_data');
        $original = _get($this->model, '_original');

        return array_diff_assoc($data, $original);
    }

    public function table(): string
    {
        return _get($this->model, '_table_name');
    }

    /** @inheritdoc */
    public function columns(): array
    {
        if ($this->isNew()) {
            $data = _get($this->model, '_data');

            return array_keys($data);
        }

        return array_keys($this->getDifferences());
    }

    public function isNew(): bool
    {
        return $this->model->is_new();
    }

    /** @inheritdoc */
    public function values(): array
    {
        if ($this->isNew()) {
            $data = _get($this->model, '_data');

            return array_values($data);
        }

        return array_values($this->getDifferences());
    }

    public function idValue(): ?string
    {
        return (string)$this->model[$this->idKey()] ?? null;
    }

    public function idKey(): ?string
    {
        return $this->idKey;
    }

    /** @inheritdoc */
    public function relations(): RelationBag
    {
        $this->extractRelationsIfNotExists();

        return $this->relations;
    }

    private function extractRelationsIfNotExists(): void
    {
        if (false === empty($this->relations)) {
            return;
        }
        $this->relations = new RelationBag();

        $dataRelations = _get($this->model, '_data_relations');
        $customData = _get($this->model, '_custom_data');

        $mergedData = array_merge($dataRelations, $customData);

        foreach (FuelRelationType::toArray() as $relationTypePropName) {
            $relation = _get($this->model, $relationTypePropName);

            if (empty($relation)) {
                continue;
            }

            foreach ($relation as $field => $meta) {
                switch ($relationTypePropName) {
                    case FuelRelationType::BELONGS_TO:
                        $relation = new BelongsTo($meta['key_from'], $meta['model_to'], $meta['key_to']);
                        if (!empty($mergedData[$field])) {
                            $relation->setRelatedData([new FuelModelAdapter($mergedData[$field])]);
                        }
                        $this->relations->add($field, $relation);
                        break;

                    case FuelRelationType::HAS_ONE:
                        $relation = new HasOne($meta['key_from'], $meta['model_to'], $meta['key_to']);
                        if (!empty($mergedData[$field])) {
                            $relation->setRelatedData([new FuelModelAdapter($mergedData[$field])]);
                        }
                        $this->relations->add($field, $relation);
                        break;

                    case FuelRelationType::HAS_MANY:
                        $entities = !empty($mergedData[$field]) ? array_map(
                            function (Model $model) {
                                return new FuelModelAdapter($model);
                            },
                            $mergedData[$field]
                        ): null;

                        $relation = new HasMany($meta['key_from'], $meta['model_to'], $meta['key_to']);

                        if (!empty($entities)) {
                            $entities = array_values($entities); # normalization, due fuels maps indexes as PK
                            $relation->setRelatedData($entities);
                        }

                        $this->relations->add($field, $relation);
                        break;

                    case FuelRelationType::MANY_TO_MANY:
                        $entities = !empty($mergedData[$field]) ? array_map(
                            function (Model $model) {
                                return new FuelModelAdapter($model);
                            },
                            $mergedData[$field]
                        ) : null;

                        $relation = new ManyToMany(
                            $meta['key_from'],
                            $meta['key_through_from'],
                            $meta['table_through'],
                            $meta['key_through_to'],
                            $meta['key_to']
                        );

                        if (!empty($entities)) {
                            $entities = array_values($entities); # normalization, due fuels maps indexes as PK
                            $relation->setRelatedData($entities);
                        }

                        $this->relations->add($field, $relation);
                        break;

                    default:
                        throw new InvalidArgumentException('Unknown relation type ' . $relationTypePropName);
                }
            }
        }
    }

    public function setId(string $id): void
    {
        if (empty($this->idKey())) {
            throw new InvalidArgumentException('Attempted to set ID value, but no ID key name specified');
        }
        $this->model[$this->idKey()] = $id;
    }

    public function generateIdValue(DBConnectionInterface $db): void
    {
        $strategy = $this->idValueGenerationStrategy();
        $strategy->handle($this, $db);
    }

    public function idValueGenerationStrategy(): IdGenerationStrategyInterface
    {
        if ($this->model instanceof HasIdStrategy) {
            return $this->model->idValueGenerationStrategy();
        }

        return new AutoIncrementIdStrategy();
    }

    /** @inheritdoc */
    public function originalClass()
    {
        return $this->model;
    }

    /** @inheritdoc */
    public function get(string $field)
    {
        return $this->model[$field];
    }

    public function set(string $field, $value): void
    {
        $this->model[$field] = $value;
    }

    public function isEmpty(): bool
    {
        return empty($this->toArray());
    }

    /** @inheritdoc */
    public function toArray(): array
    {
        return $this->model->to_array();
    }

    public function objectHash(): string
    {
        return $this->objectHash;
    }

    public function addPostPersist(Closure $closure): void
    {
        $this->closures[] = $closure;
    }

    /** @inheritdoc */
    public function getPostPersistClosures(): array
    {
        return $this->closures;
    }
}
