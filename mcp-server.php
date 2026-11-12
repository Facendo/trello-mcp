#!/usr/bin/env php
<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use Mcp\Server\Server;
use Mcp\Server\Transport\StdioTransport;
use McpTrello\ToolRegistrar;
use McpTrello\TrelloClient;

require __DIR__.'/vendor/autoload.php';

// Las credenciales pueden venir del entorno (config de Claude) o de .env.
Dotenv::createImmutable(__DIR__)->safeLoad();

$env = static fn (string $k): string => (string) ($_SERVER[$k] ?? $_ENV[$k] ?? getenv($k) ?: '');

try {
    $trello = new TrelloClient($env('TRELLO_API_KEY'), $env('TRELLO_TOKEN'));
} catch (\Throwable $e) {
    fwrite(STDERR, '[mcp-trello] '.$e->getMessage().PHP_EOL);
    exit(1);
}

$builder = Server::builder()
    ->setServerInfo('mcp-trello', '1.0.0')
    ->setInstructions('Gestiona tableros de Trello. Empieza con trello_list_boards para obtener IDs. No existen herramientas de borrado: se archiva.');

exit(ToolRegistrar::register($builder, $trello)->build()->run(new StdioTransport()));
