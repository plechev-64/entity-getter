<?php

namespace Src\Core\EntityGetter\WpQuery;

use WP_Query;

class PostGetter extends WpQueryEntityGetter
{

    /** поиск по заголовку и наименованию терминов указанных таксономий */
    public const SEARCH_IN_TITLE_AND_TERM = 'searchInTitleAndTerm';

    /** поиск по заголовку и указанным метаполям */
    public const SEARCH_IN_TITLE_AND_META = 'searchInTitleAndMeta';

    /**
     * Поиск только по заголовку записи
     */
    public const SEARCH_IN_TITLE = 'searchInTitle';

    protected function getQueryName(): string
    {
        return WP_Query::class;
    }

    protected function getFilterRules(): array
    {
        return [
            self::SEARCH_IN_TITLE_AND_TERM => function (array &$args, SearchInTitleAndTermContext $context) {
                $this->addWpFilter('posts_clauses', 'searchInTitleAndTerm', 10, [
                    self::SEARCH_IN_TITLE_AND_TERM => $context
                ]);
            },
            self::SEARCH_IN_TITLE_AND_META => function (array &$args, SearchInTitleAndMetaContext $context) {
                $this->addWpFilter('posts_clauses', 'searchInTitleAndMeta', 10, [
                    self::SEARCH_IN_TITLE_AND_META => $context
                ]);
            },
            self::SEARCH_IN_TITLE          => function (array &$args, string $search) {
                $this->addWpFilter('posts_clauses', 'searchInTitle', 10, [
                    self::SEARCH_IN_TITLE => $search
                ]);
            },
        ];
    }

    public function searchInTitleAndTerm($clauses)
    {
        global $wpdb;

        /** @var SearchInTitleAndTermContext $context */
        $context = $this->getFilterContext(self::SEARCH_IN_TITLE_AND_TERM);

        $clauses['join']  .= " LEFT JOIN $wpdb->term_relationships tr ON $wpdb->posts.ID = tr.object_id ";
        $clauses['join']  .= " LEFT JOIN $wpdb->term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id ";
        $clauses['join']  .= " LEFT JOIN $wpdb->terms t ON tt.term_id = t.term_id ";
        $where            = $wpdb->prepare(
            " OR (t.name ILIKE %s and tt.taxonomy IN (%s)) ",
            '%' . $wpdb->esc_like($context->search) . '%',
            implode(',', $context->taxonomies)
        );
        $clauses['where'] .= " AND (" .
                             $wpdb->prepare("$wpdb->posts.post_title ILIKE %s",
                                 '%' . $wpdb->esc_like($context->search) . '%')
                             . " $where) ";

        $clauses['groupby'] = " $wpdb->posts.ID ";

        return $clauses;
    }

    public function searchInTitleAndMeta($clauses)
    {
        global $wpdb;

        /** @var SearchInTitleAndMetaContext $context */
        $context = $this->getFilterContext(self::SEARCH_IN_TITLE_AND_META);

        $where = "";
        foreach ($context->metaKeys as $k => $metaKey) {
            $metaKey         = esc_sql($metaKey);
            $clauses['join'] .= " LEFT JOIN $wpdb->postmeta searchMeta$k ON $wpdb->posts.ID = searchMeta$k.post_id ";
            $where           .= $wpdb->prepare(
                " OR (searchMeta$k.meta_key='$metaKey' AND searchMeta$k.meta_value ILIKE %s) ",
                '%' . $wpdb->esc_like($context->search) . '%'
            );
        }

        $clauses['where'] .= " AND (" .
                             $wpdb->prepare("$wpdb->posts.post_title ILIKE %s",
                                 '%' . $wpdb->esc_like($context->search) . '%')
                             . " $where) ";

        $clauses['groupby'] = " $wpdb->posts.ID ";

        return $clauses;
    }

    public function searchInTitle($clauses)
    {
        global $wpdb;

        $search = $this->getFilterContext(self::SEARCH_IN_TITLE);

        $clauses['where'] .= $wpdb->prepare(" AND ($wpdb->posts.post_title ILIKE %s)",
            '%' . $wpdb->esc_like($search) . '%');

        return $clauses;
    }
}
