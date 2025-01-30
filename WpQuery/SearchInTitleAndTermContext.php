<?php

namespace Src\Core\EntityGetter\WpQuery;

class SearchInTitleAndTermContext
{
    public string $search;
    public array $taxonomies;

    /**
     * @param string $search
     * @param array $taxonomies
     */
    public function __construct(string $search, array $taxonomies)
    {
        $this->search     = $search;
        $this->taxonomies = $taxonomies;
    }

}