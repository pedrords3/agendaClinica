<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class ValidationException extends RuntimeException
{
    public function __construct(public readonly array $errors)
    {
        parent::__construct(reset($errors) ?: 'Revise os dados informados.');
    }
}

