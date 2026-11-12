<?php

declare(strict_types=1);

namespace McpTrello;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Mcp\Server\Exception\ToolCallException;

/**
 * Cliente mínimo de la API REST de Trello (GET, POST, PUT; sin DELETE a propósito).
 */
final class TrelloClient
{
    public const BASE_URI = 'https://api.trello.com/1/';

    private ClientInterface $http;

    /** @var callable(int): void */
    private $sleeper;

    public function __construct(
        private readonly string $apiKey,
        private readonly string $token,
        ?ClientInterface $http = null,
        ?callable $sleeper = null,
        private readonly int $maxRetries = 3,
    ) {
        if ($apiKey === '' || $token === '') {
            throw new ToolCallException('Faltan TRELLO_API_KEY o TRELLO_TOKEN en el entorno (.env).');
        }
        $this->http = $http ?? new Client(['base_uri' => self::BASE_URI, 'timeout' => 20, 'http_errors' => false]);
        $this->sleeper = $sleeper ?? static function (int $seconds): void {
            sleep($seconds);
        };
    }

    /** @param array<string, mixed> $query */
    public function get(string $path, array $query = []): mixed
    {
        return $this->request('GET', $path, $query);
    }

    /** @param array<string, mixed> $params */
    public function post(string $path, array $params = []): mixed
    {
        return $this->request('POST', $path, [], $params);
    }

    /** @param array<string, mixed> $params */
    public function put(string $path, array $params = []): mixed
    {
        return $this->request('PUT', $path, [], $params);
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $form
     */
    private function request(string $method, string $path, array $query = [], array $form = []): mixed
    {
        $options = [
            'http_errors' => false,
            'query' => ['key' => $this->apiKey, 'token' => $this->token] + self::clean($query),
        ];
        if ($form !== []) {
            $options['form_params'] = self::clean($form);
        }

        for ($attempt = 0;; ++$attempt) {
            try {
                $response = $this->http->request($method, ltrim($path, '/'), $options);
            } catch (GuzzleException $e) {
                throw new ToolCallException('No se pudo conectar con Trello: '.$e->getMessage());
            }

            $status = $response->getStatusCode();
            if ($status === 429 && $attempt < $this->maxRetries) {
                $wait = (int) ($response->getHeaderLine('Retry-After') ?: 2 ** $attempt);
                ($this->sleeper)(max(1, min($wait, 30)));
                continue;
            }
            break;
        }

        $body = (string) $response->getBody();
        if ($status >= 200 && $status < 300) {
            $data = json_decode($body, true);

            return json_last_error() === JSON_ERROR_NONE ? $data : $body;
        }

        throw new ToolCallException(self::describeError($status, $body));
    }

    private static function describeError(int $status, string $body): string
    {
        $detail = trim(mb_substr($body, 0, 200));

        return match (true) {
            $status === 400 => "Trello rechazó la petición (400): parámetros inválidos. $detail",
            $status === 401 => 'Trello respondió 401: la API key o el token son inválidos o han expirado. Revisa TRELLO_API_KEY y TRELLO_TOKEN.',
            $status === 403 => 'Trello respondió 403: el token no tiene permisos sobre este recurso (¿token de solo lectura?).',
            $status === 404 => 'Trello respondió 404: el recurso no existe o no tienes acceso. Verifica el ID.',
            $status === 429 => 'Trello respondió 429: límite de peticiones excedido tras varios reintentos. Espera un momento e inténtalo de nuevo.',
            $status >= 500 => "Trello tiene problemas internos ($status). Inténtalo más tarde.",
            default => "Error inesperado de Trello ($status): $detail",
        };
    }

    /**
     * Quita nulos y convierte booleanos/arrays al formato que espera Trello.
     *
     * @param array<string, mixed> $params
     *
     * @return array<string, string|int|float>
     */
    private static function clean(array $params): array
    {
        $out = [];
        foreach ($params as $key => $value) {
            if ($value === null) {
                continue;
            }
            $out[$key] = match (true) {
                is_bool($value) => $value ? 'true' : 'false',
                is_array($value) => implode(',', $value),
                default => $value,
            };
        }

        return $out;
    }
}
