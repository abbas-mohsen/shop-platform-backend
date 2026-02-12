<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a checkout cannot proceed due to insufficient product stock.
 */
class InsufficientStockException extends RuntimeException
{
    public function __construct(string $message = 'Insufficient stock', int $code = 422)
    {
        parent::__construct($message, $code);
    }
}
