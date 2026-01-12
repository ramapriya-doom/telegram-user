<?php

namespace Ramapriya\Telegram\User;

use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Dotenv\Dotenv;

class App
{
    private Dotenv $env;
    private Database $db;
    private DTO $dto;

    private LoggerInterface $logger;

    private array $update;
    private array $payload;

    public function __construct(private readonly string $envFilePath)
    {
        $this->env = new Dotenv();
        $this->env->loadEnv($this->envFilePath);

        $this->db = new Database($_ENV['USER_TABLE_NAME']);
        $this->logger = new Logger(__CLASS__);
        $this->logger->pushHandler(new StreamHandler($_SERVER['DOCUMENT_ROOT'] . '/logs/app.log'));

        $this->parseUpdate();
    }

    public function parseUpdate(): void
    {
        $json = file_get_contents('php://input');
        $this->update = json_decode($json, true);

        $this->logger->debug('webhook update', $this->update);

        $this->payload = match (true) {
            array_key_exists('message', $this->update) => $this->update['message'],
            array_key_exists('callback_query', $this->update) => $this->update['callback_query'],
            array_key_exists('business_message', $this->update) => $this->update['business_message'],
        };

        $this->dto = new DTO(
            $this->payload['from']['id'],
            $this->payload['from']['first_name'],
            $this->payload['from']['last_name'] ?? null,
            $this->payload['from']['username'] ?? null,
            $this->payload['from']['language_code'] ?? null,
        );
    }

    public function saveUser(): void
    {
        $existing = $this->db->find(['id' => $this->dto->id], ['id']);

        if (!empty($existing)) {
            $this->logger->debug('user exists', current($existing));
            return;
        }

        try {
            $this->db->save($this->dto->toArray());
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage(), [$e->getTraceAsString()]);
        }
    }

}