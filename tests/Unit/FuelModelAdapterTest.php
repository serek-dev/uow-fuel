<?php

namespace Tests\Unit;

use Orm\Model;
use PHPUnit\Framework\TestCase;
use Stwarog\UowFuel\FuelModelAdapter;

final class FuelModelAdapterTest extends TestCase
{
    /** @test */
    public function noLongerNew_modelIsNew_switchesToFalse(): void
    {
        // Given Fuel`s model
        $model = new Model([], false);

        // And Fuel`s model adapter
        $adapter = new FuelModelAdapter($model);

        // When no longer new is called
        $adapter->noLongerNew();

        // Then is should not be new
        $this->assertFalse($adapter->isNew());
    }
}
