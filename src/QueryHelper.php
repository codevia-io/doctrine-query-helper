<?php

namespace Codevia\DoctrineQueryHelper;

use Doctrine\ORM\QueryBuilder;
use Psr\Http\Message\ServerRequestInterface;

abstract class QueryHelper
{
    /**
     * Search 
     * @param QueryBuilder           $queryBuilder The Doctrine QueryBuilder
     * @param ServerRequestInterface $request      The PSR-7 Request object
     * @param string $alias  The entity alias
     * @param array  $fields The entity's fields it should search in
     * @param string $key    The key where the search query is
     * @return void 
     */
    public static function search(
        QueryBuilder $queryBuilder,
        ServerRequestInterface $request,
        string $alias,
        array $fields,
        string $key = 'search'
    ): void {
        $query = $request->getQueryParams()[$key] ?? null;

        if ($query === null || strlen($query) === 0) {
            // Nothing to query
            return;
        }

        $queryBuilder->andWhere(
            $queryBuilder->expr()->orX(
                ...array_map(function (string $field) use ($alias, $queryBuilder) {
                    return $queryBuilder->expr()->like("$alias.$field", ':query');
                }, $fields)
            ),
        );
        
        $queryBuilder->setParameter('query', '%' . $query . '%');
    }
}
