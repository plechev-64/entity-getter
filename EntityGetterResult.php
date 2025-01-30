<?php

namespace Gosweb\Core\EntityGetter;

class EntityGetterResult
{
    public array $items = [];
    public int $total = 0;
    public int $found = 0;
    public int $number = 0;
    public int $currentPage = 1;
    public int $maxNumPages = 1;
}