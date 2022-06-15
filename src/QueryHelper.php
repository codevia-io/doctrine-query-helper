<?php

namespace Codevia\DoctrineQueryHelper;

use Codevia\RequestAnalyzer\RequestAnalizer;
use Doctrine\ORM\QueryBuilder;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

abstract class QueryHelper
{
    /**
     * Search in entity fields for the requested value.
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

    /**
     * Paginate a Doctrine query.
     * @param QueryBuilder           $queryBuilder The Doctrine QueryBuilder
     * @param ServerRequestInterface $request      The PSR-7 Request object
     * @return void 
     */
    public static function paginate(
        QueryBuilder $queryBuilder,
        ServerRequestInterface $request
    ): void
    {
        $pagination = RequestAnalizer::getPagination($request);
        $page = $pagination['page'];
        $limit = $pagination['limit'];

        $queryBuilder->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);
    }


    /**
     * Easily include relationships
     * @param QueryBuilder $qb            The Doctrine QueryBuilder
     * @param string       $alias         The entity alias
     * @param array        $relationships The relationships fields
     * @return void 
     * @throws RuntimeException 
     * @throws InvalidArgumentException 
     */
    public function includeRelationships(
        QueryBuilder $qb,
        string $alias,
        array $relationships
    ): void {
        foreach ($relationships as $field => $fieldAlias) {
            $qb->leftJoin($alias . '.' . $field, $fieldAlias);
            $qb->addSelect($fieldAlias);
        }
    }
}
