<?php

declare(strict_types=1);

namespace App\Application\Query;

interface QueryBusInterface
{
    /**
     * Ask a query and get the result
     *
     * @param object $query The query to ask
     * @return mixed The result from the query handler
     */
    public function ask(object $query): mixed;
}