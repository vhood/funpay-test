<?php

namespace FpDbTest;

use Exception;
use mysqli;

class Database implements DatabaseInterface
{
    private mysqli $mysqli;

    public function __construct(mysqli $mysqli)
    {
        $this->mysqli = $mysqli;
    }

    public function buildQuery(string $query, array $args = []): string
    {
        if (empty($args)) {
            return $query;
        }

        foreach ($args as $arg) {
            $query = preg_replace(
                '/\?([dfa# ])/',
                ':$1:',
                $query,
                1
            );

            // условные блоки
            if ($arg === $this->skip()) {
                $query = preg_replace('/{.*}/', '', $query, 1);
                continue;
            }

            // Параметры ?, ?d, ?f могут принимать значения null (в этом случае в шаблон вставляется NULL)
            if ($arg === null && preg_match('/:[df ]:/', $query)) {
                $query = preg_replace('/:[df ]:/', 'NULL', $query);
                continue;
            }

            // идентификатор или массив идентификаторов
            if (str_contains($query, ':#:')) {
                $query = str_replace(
                    ':#:',
                    sprintf('`%s`', is_array($arg) ? implode('`, `', $arg) : $arg),
                    $query
                );
                continue;
            }

            // спецификатор не указан
            if (str_contains($query, ': :')) {
                $query = str_replace(': :', $this->parsedValue($arg) . ' ', $query);
                continue;
            }

            // конвертация в целое число
            if (str_contains($query, ':d:')) {
                $query = str_replace(':d:', (int)$arg, $query);
                continue;
            }

            // конвертация в число с плавающей точкой
            if (str_contains($query, ':f:')) {
                $query = str_replace(':f:', (float)$arg, $query);
                continue;
            }

            // массив значений
            if (str_contains($query, ':a:')) {
                if (!array_is_list($arg)) {
                    $values = [];
                    foreach ($arg as $key => $value) {
                        $values[] = "`$key` = {$this->parsedValue($value)}";
                    }

                    $arg = $values;
                }

                $query = str_replace(':a:', implode(', ', $arg), $query);
                continue;
            }
        }

        // не вырезанные условные блоки
        $query = str_replace(['{', '}'], '', $query);

        return $query;
    }

    public function skip()
    {
        return '#skip';
    }

    // Если спецификатор не указан, то используется тип переданного значения,
    // но допускаются только типы string, int, float, bool (приводится к 0 или 1) и null.
    private function parsedValue($value)
    {
        if ($value === null) {
            return "NULL";
        }

        if (is_bool($value)) {
            return (int)$value;
        }

        if (!in_array(gettype($value), ['integer', 'double', 'string'], true)) {
            throw new Exception('incorrect value type');
        }

        return is_string($value) ? sprintf("'%s'", $value) : $value;
    }
}
