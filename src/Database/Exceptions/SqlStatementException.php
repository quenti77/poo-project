<?php

namespace Tuto\Database\Exceptions;

use RuntimeException;
use Throwable;
use Tuto\Database\StatementInterface;

class SqlStatementException extends RuntimeException
{
    public function __construct(string|StatementInterface $statement, Throwable $previous)
    {
        $statementMessage = is_string($statement) ? $statement : 'previous request';
        $message = sprintf(
            "Error during request with request '%s'. Error: '%s'",
            $statementMessage,
            $previous->getMessage(),
        );

        parent::__construct($message, $previous->getCode(), $previous);
    }
}