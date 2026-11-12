<?php

declare(strict_types=1);

namespace McpTrello;

use Mcp\Server\Builder;
use Mcp\Server\Capability\Attribute\McpTool;
use McpTrello\Tools\BoardTools;
use McpTrello\Tools\CardTools;
use McpTrello\Tools\CollaborationTools;
use McpTrello\Tools\ListTools;
use McpTrello\Tools\SearchTools;

/**
 * Registra explícitamente cada método con #[McpTool] (sin discovery del filesystem).
 */
final class ToolRegistrar
{
    public static function register(Builder $builder, TrelloClient $trello): Builder
    {
        $handlers = [
            new BoardTools($trello), new ListTools($trello), new CardTools($trello),
            new CollaborationTools($trello), new SearchTools($trello),
        ];

        foreach ($handlers as $handler) {
            foreach ((new \ReflectionObject($handler))->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                $attr = $method->getAttributes(McpTool::class)[0] ?? null;
                if ($attr === null) {
                    continue;
                }
                /** @var McpTool $tool */
                $tool = $attr->newInstance();
                $builder->addTool([$handler, $method->getName()], name: $tool->name);
            }
        }

        return $builder;
    }
}
