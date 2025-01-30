<?php

use Gosweb\Core\App;
use Gosweb\Core\EntityGetter\WpQuery\PostGetter;

$container = App::getInstance()
                ->container();

/** @var PostGetter $templateHandler */
$getter = $container->get(PostGetter::class);

$getter->setFilters([
    PostGetter::FILTER_BY_TYPES     => ['post', 'page'],
    PostGetter::FILTER_BY_AUTHOR_ID => 10
    ])->setPage(2)
;

$getter->getData();