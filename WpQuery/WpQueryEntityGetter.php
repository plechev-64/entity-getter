<?php

namespace Src\Core\EntityGetter\WpQuery;

use Src\Core\EntityGetter\EntityGetterInterface;
use Src\Core\EntityGetter\EntityGetterResult;
use Src\Core\Enum\SortOrder;
use WP_Query;

abstract class WpQueryEntityGetter implements EntityGetterInterface
{
    protected int $number = 20;
    protected array $filters = [
        'post_status' => 'publish',
    ];
    private array $filterContext = [];
    private array $wpFilters = [];

    abstract protected function getQueryName(): string;

    /**
     * @return int
     */
    public function getNumber(): int
    {
        return $this->number;
    }

    public function setOrderBy(string|array $by, SortOrder $order = SortOrder::DESC): self
    {
        $this->filters['order'] = $order;
        $this->filters['orderby'] = $by;

        return $this;
    }

    public function setNumber(int $number): self
    {
        $this->number = $number;
        $this->filters['posts_per_page'] = $number;

        return $this;
    }

    public function setPage(int $page): self
    {
        $this->filters['paged'] = $page;

        return $this;
    }

    public function getResult(): EntityGetterResult
    {
        /** @var WP_Query $queryClassName */
        $queryClassName = $this->getQueryName();

        $this->setupPageData();

        $filters = $this->getFilters();

        //отключаем подсчет постов вп, т.к. он не корректно преобразуется регулярками. Считаем отдельным запросом.-
        $filters['no_found_rows'] = true;

        $query = new $queryClassName(
            $filters
        );

        $filters['posts_per_page'] = -1;
        $filters['fields'] = 'ids';

        $totalResults = new $queryClassName(
            $filters
        );

        $maxNumberPages = ceil($totalResults->post_count / $this->getNumber());

        if ($this->wpFilters) {
            foreach ($this->wpFilters as $filter) {
                remove_filter($filter[0], $filter[1], $filter[2]);
            }
            $this->wpFilters = [];
        }

        $result = new EntityGetterResult();
        $result->items = $query->posts;
        $result->found = count($result->items);
        $result->currentPage = $this->filters['paged'];
        $result->maxNumPages = $maxNumberPages;
        $result->total = $totalResults->post_count;
        $result->number = $this->number;

        return $result;
    }

    public function setFilters(array $filters): self
    {

        if (isset($filters['posts_per_page'])) {
            $this->number = $filters['posts_per_page'];
        }

        $this->filters = $filters;

        return $this;
    }

    protected function getFilterRules(): array
    {
        return [];
    }

    private function setupPageData(): void
    {

        if (!isset($this->filters['paged'])) {
            $this->filters['paged'] = function_exists('get_query_var') && get_query_var('paged') ? get_query_var('paged') : 1;
        }

    }

    private function getFilters(): array
    {
        $filters = $this->filters;
        if ($filterRules = $this->getFilterRules()) {
            foreach ($filterRules as $key => $rule) {
                if (isset($filters[$key])) {
                    $rule($filters, $filters[$key]);
                }
            }
        }

        return $filters;
    }

    protected function addWpFilter(string $filterName, string $methodName, int $priority, ?array $context = null): void
    {
        if ($context !== null) {
            $this->filterContext = array_merge($this->filterContext, $context);
        }
        $this->wpFilters[] = [$filterName, [$this, $methodName], $priority];
        add_filter($filterName, [$this, $methodName], $priority);
    }

    protected function getFilterContext(string $filterName): mixed
    {
        return $this->filterContext[$filterName] ?? null;
    }
}
