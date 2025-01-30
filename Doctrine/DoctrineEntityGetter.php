<?php

namespace Gosweb\Core\EntityGetter\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Gosweb\Core\EntityGetter\EntityGetterInterface;
use Gosweb\Core\EntityGetter\EntityGetterResult;
use Gosweb\Core\Enum\SortOrder;

abstract class DoctrineEntityGetter implements EntityGetterInterface
{
    protected int $total = 0;
    protected array $sort = [
        'order' => 'DESC',
        'by' => ''
    ];
    protected ?int $page = null;
    protected int $offset = 0;
    protected int $number = 20;
    protected int $pages = 0;
    protected array $filters = [];
    protected array $workFilters = [];

    protected readonly EntityManagerInterface $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    abstract protected function getEntityClassName(): string;

    abstract protected function getAlias(): string;

    abstract protected function getMainQuery(): QueryBuilder;

    /**
     * @param string $by
     * @param SortOrder $order
     *
     * @return DoctrineEntityGetter
     */
    public function setOrderBy(string $by, SortOrder $order = SortOrder::DESC): DoctrineEntityGetter
    {
        $this->sort = [
            'by' => $by,
            'order' => $order
        ];

        return $this;
    }

    public function setFilters(array $filters): self
    {
        $this->filters = $filters;

        return $this;
    }

    public function setNumber(int $number): self
    {
        $this->number = $number;

        return $this;
    }

    public function setPage(int $page): self
    {
        $this->page = $page;

        return $this;
    }

    public function getTotal(QueryBuilder|null $query = null): int
    {
        if ($query instanceof QueryBuilder) {
            $countableQuery = clone $query;
        } else {
            $countableQuery = $this->getMainQuery();
        }

        return $this->filterQuery($countableQuery)
            ->select('count(' . $this->getAlias() . ')')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getQuery(): string
    {
        return $this->filterQuery($this->getMainQuery())
            ->getQuery()
            ->getSQL();
    }

    private function setupPageData(mixed $query = null): void
    {

        if ($this->page === null) {
            $this->page = function_exists('get_query_var') && get_query_var('paged') ? get_query_var('paged') : 1;
        }

        $this->offset = $this->number > 0 ? ($this->page - 1) * $this->number : 0;

    }

    protected function getFilterRules(): array
    {
        return [];
    }

    protected function filterQueryByRule(string $filterName, QueryBuilder $query, mixed $value): QueryBuilder
    {
        $filter = $this->getFilterRules()[$filterName] ?? null;

        return $filter ? $filter($query, $value) : $query;
    }

    public function getResult(): EntityGetterResult
    {

        $query = $this->getMainQuery();

        if ($this->number > 0) {
            $this->setupPageData($query);
            $query->setMaxResults($this->number);
        }

        $metaData = $this->entityManager->getClassMetadata($this->getEntityClassName());

        $items = $this->filterQuery($query)
                      ->setFirstResult($this->offset)
                      ->groupBy(sprintf('%s.%s', $this->getAlias(), $metaData->getIdentifier()[0]))
                      ->getQuery()
                      ->getResult();

        $result              = new EntityGetterResult();
        $result->items       = $items;
        $result->found       = count($items);
        $result->currentPage = $this->page;
        $result->maxNumPages = $this->pages;
        $result->total       = $this->total;

        return $result;

    }

    private function filterQuery(QueryBuilder $query): QueryBuilder
    {

        $filters = $this->filters;

        if ($filterRules = $this->getFilterRules()) {
            foreach ($filterRules as $key => $rule) {
                if (isset($filters[$key])) {
                    $this->workFilters[$key] = $filters[$key];
                    $query = $rule($query, $filters[$key]);
                }
            }
        }

        return $query;
    }

}
