<?php

namespace Ramapriya\Telegram\User;

use PDO;

class Database
{
    private \PDO $connection;
    public function __construct(private readonly string $userTableName = 'users')
    {
        $this->validateEnvVariables();
        $this->initConnection();
    }

    private function validateEnvVariables()
    {
        $variables = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASSWORD', 'DB_DRIVER'];
        $errors = [];

        if (empty($_ENV)) {
            throw new \Exception('Empty $_ENV variables');
        }

        foreach ($variables as $variable) {
            if (!array_key_exists($variable, $_ENV)) {
                $errors[] = $variable;
            }
        }

        if (!empty($errors)) {
            $message = sprintf('$_ENV variables not set: %s', implode(', ', $errors));
            throw new \Exception($message);
        }
    }

    private function initConnection(): void
    {
        $dsn = sprintf('%s:host=%s;dbname=%s', $_ENV['DB_DRIVER'], $_ENV['DB_HOST'], $_ENV['DB_NAME']);
        $this->connection = new \PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASSWORD']);
    }

    public function getConnection(): \PDO
    {
        return $this->connection;
    }

    public function find(array $filter = [], array $select = ['*']): array
    {
        $sql = sprintf('SELECT %s FROM %s', implode(', ', $select), $this->userTableName);

        if (!empty($filter)) {
            $sql .= ' WHERE ';
            foreach (array_keys($filter) as $key) {
                $sql .= sprintf('%s = :%s', $key, $key);

            }
        }

        $stmt = $this->connection->prepare($sql);
        foreach ($filter as $field => $value) {
            $stmt->bindValue(':' . $field, $value);
        }

        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function save(array $data): void
    {
        $fields = implode(', ', array_keys($data));
        $values = [];

        foreach ($data as $value) {
            if (is_numeric($value)) {
                $values[] = (int)$value;
            } else {
                $values[] = sprintf('"%s"', $value);
            }
        }

        $sql = sprintf('INSERT INTO %s (%s) VALUES (%s)', $this->userTableName, $fields, implode(', ', $values));
        $result = $this->connection->exec($sql);

        if (!$result) {
            $error = sprintf('%s [%s]: %s', ...$this->connection->errorInfo());
            throw new \Exception($error);
        }
    }
}