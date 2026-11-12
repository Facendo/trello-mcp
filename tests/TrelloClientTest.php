<?php

declare(strict_types=1);

namespace McpTrello\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use McpTrello\Trim;
use McpTrello\TrelloClient;
use Mcp\Server\Exception\ToolCallException;
use PHPUnit\Framework\TestCase;

final class TrelloClientTest extends TestCase
{
    /** @var list<array{request: \Psr\Http\Message\RequestInterface}> */
    private array $history = [];

    /** @var list<int> */
    private array $sleeps = [];

    private function client(array $responses): TrelloClient
    {
        $this->history = [];
        $this->sleeps = [];
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->history));

        return new TrelloClient('K', 'T', new Client(['handler' => $stack, 'base_uri' => TrelloClient::BASE_URI, 'http_errors' => false]), function (int $s): void {
            $this->sleeps[] = $s;
        });
    }

    public function testGetSendsAuthAndDecodesJson(): void
    {
        $c = $this->client([new Response(200, [], '[{"id":"1"}]')]);
        $this->assertSame([['id' => '1']], $c->get('members/me/boards', ['filter' => 'open']));
        $uri = $this->history[0]['request']->getUri();
        $this->assertSame('/1/members/me/boards', $uri->getPath());
        $this->assertStringContainsString('key=K', $uri->getQuery());
        $this->assertStringContainsString('token=T', $uri->getQuery());
        $this->assertStringContainsString('filter=open', $uri->getQuery());
    }

    public function testPostSendsFormAndDropsNulls(): void
    {
        $c = $this->client([new Response(200, [], '{"id":"9"}')]);
        $c->post('cards', ['name' => 'X', 'desc' => null, 'idLabels' => ['a', 'b'], 'closed' => true]);
        $this->assertSame('POST', $this->history[0]['request']->getMethod());
        $body = (string) $this->history[0]['request']->getBody();
        $this->assertStringContainsString('name=X', $body);
        $this->assertStringNotContainsString('desc', $body);
        $this->assertStringContainsString('idLabels=a%2Cb', $body);
        $this->assertStringContainsString('closed=true', $body);
    }

    public function testPutUsesPut(): void
    {
        $c = $this->client([new Response(200, [], '{}')]);
        $c->put('cards/1', ['closed' => true]);
        $this->assertSame('PUT', $this->history[0]['request']->getMethod());
    }

    /** @dataProvider errors */
    #[\PHPUnit\Framework\Attributes\DataProvider('errors')]
    public function testReadableErrors(int $status, string $expected): void
    {
        $c = $this->client([new Response($status, [], 'x')]);
        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage($expected);
        $c->get('cards/1');
    }

    public static function errors(): array
    {
        return [[401, 'inválidos'], [404, 'no existe'], [403, 'permisos']];
    }

    public function testRetriesOn429ThenSucceeds(): void
    {
        $c = $this->client([new Response(429, ['Retry-After' => '3']), new Response(200, [], '{"ok":1}')]);
        $this->assertSame(['ok' => 1], $c->get('x'));
        $this->assertSame([3], $this->sleeps);
        $this->assertCount(2, $this->history);
    }

    public function testGivesUpAfterMaxRetries(): void
    {
        $c = $this->client(array_fill(0, 4, new Response(429)));
        $this->expectExceptionMessage('429');
        try {
            $c->get('x');
        } finally {
            $this->assertCount(4, $this->history);
        }
    }

    public function testMissingCredentials(): void
    {
        $this->expectException(ToolCallException::class);
        new TrelloClient('', '');
    }

    public function testTrimCardKeepsUsefulFields(): void
    {
        $card = Trim::card([
            'id' => '1', 'name' => 'N', 'desc' => '', 'badges' => ['big' => 1], 'labels' => [['id' => 'l', 'name' => 'Bug', 'color' => 'red']],
        ]);
        $this->assertSame(['id' => '1', 'name' => 'N', 'labels' => ['Bug [red] (l)']], $card);
    }
}
