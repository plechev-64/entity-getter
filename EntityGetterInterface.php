<?php

namespace Src\Core\EntityGetter;

use Src\Core\Enum\SortOrder;

interface EntityGetterInterface
{
    public function setOrderBy(string $by, SortOrder $order = SortOrder::DESC);
    public function setFilters(array $filters): self;
    public function setPage(int $page): self;
    public function setNumber(int $number): self;
    public function getNumber(): int;
    public function getResult(): EntityGetterResult;
}