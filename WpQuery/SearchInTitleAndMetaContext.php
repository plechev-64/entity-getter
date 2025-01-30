<?php

namespace Gosweb\Core\EntityGetter\WpQuery;

class SearchInTitleAndMetaContext
{
    public string $search;
    public array $metaKeys;

    /**
     * @param string $search
     * @param array $metaKeys
     */
    public function __construct(string $search, array $metaKeys)
    {
        $this->search   = $search;
        $this->metaKeys = $metaKeys;
    }

}