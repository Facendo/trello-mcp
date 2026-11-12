<?php

declare(strict_types=1);

namespace McpTrello\Tools;

use Mcp\Server\Capability\Attribute\McpTool;
use McpTrello\Trim;
use McpTrello\TrelloClient;

final class SearchTools
{
    public function __construct(private readonly TrelloClient $trello)
    {
    }

    /**
     * Busca tarjetas por texto en todos los tableros del usuario. Admite operadores de Trello como
     * "@usuario", "label:rojo", "due:week", "is:open".
     *
     * @param string      $query   Texto o consulta de búsqueda
     * @param int         $limit   Máximo de resultados (1-50)
     * @param string|null $boardId Limitar la búsqueda a un tablero (opcional)
     */
    #[McpTool(name: 'trello_search_cards')]
    public function searchCards(string $query, int $limit = 20, ?string $boardId = null): array
    {
        $r = $this->trello->get('search', [
            'query' => $query,
            'modelTypes' => 'cards',
            'cards_limit' => max(1, min($limit, 50)),
            'card_fields' => 'name,due,idList,idBoard,url,labels,closed',
            'partial' => 'true',
            'idBoards' => $boardId,
        ]);

        return array_map(Trim::card(...), $r['cards'] ?? []);
    }
}
