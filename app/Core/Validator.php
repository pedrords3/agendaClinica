<?php

declare(strict_types=1);

namespace App\Core;

final class Validator
{
    private array $errors = [];

    public function required(array $data, string $field, string $label, int $max = 255): self
    {
        $value = trim((string) ($data[$field] ?? ''));
        if ($value === '') {
            $this->errors[$field] = "{$label} é obrigatório.";
        } elseif (mb_strlen($value) > $max) {
            $this->errors[$field] = "{$label} deve ter no máximo {$max} caracteres.";
        }
        return $this;
    }

    public function email(array $data, string $field, bool $required = false): self
    {
        $value = trim((string) ($data[$field] ?? ''));
        if ($value === '' && !$required) {
            return $this;
        }
        if (!filter_var($value, FILTER_VALIDATE_EMAIL) || mb_strlen($value) > 190) {
            $this->errors[$field] = 'Informe um e-mail válido.';
        }
        return $this;
    }

    public function integer(array $data, string $field, int $min = 1, ?int $max = null): self
    {
        $value = filter_var($data[$field] ?? null, FILTER_VALIDATE_INT);
        if ($value === false || $value < $min || ($max !== null && $value > $max)) {
            $this->errors[$field] = 'Informe um valor válido.';
        }
        return $this;
    }

    public function date(array $data, string $field): self
    {
        $value = (string) ($data[$field] ?? '');
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if (!$date || $date->format('Y-m-d') !== $value) {
            $this->errors[$field] = 'Informe uma data válida.';
        }
        return $this;
    }

    public function oneOf(array $data, string $field, array $allowed): self
    {
        if (!in_array((string) ($data[$field] ?? ''), $allowed, true)) {
            $this->errors[$field] = 'Selecione uma opção válida.';
        }
        return $this;
    }

    public function throw(): void
    {
        if ($this->errors !== []) {
            throw new ValidationException($this->errors);
        }
    }
}

