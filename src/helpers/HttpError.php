<?php
declare(strict_types=1);

/** Erro de negócio com código HTTP (404, 403, 400...). */
final class HttpError extends RuntimeException
{
    public function __construct(public readonly int $status, string $message)
    {
        parent::__construct($message);
    }
}
