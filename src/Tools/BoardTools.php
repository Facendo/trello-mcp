<?php

declare(strict_types=1);

namespace McpTrello\Tools;

use Mcp\Server\Capability\Attribute\McpTool;
use McpTrello\Trim;
use McpTrello\TrelloClient;

final class BoardTools
{
    public function __construct(private readonly TrelloClient $trello)
    {
    }

    /** Lista los tableros abiertos del usuario. Úsala primero para obtener los IDs de tablero. */
    #[McpTool(name: 'trello_list_boards')]
    public function listBoards(): array
    {
        return Trim::each(
            $this->trello->get('members/me/boards', ['filter' => 'open', 'fields' => 'name,desc,url']),
            ['id', 'name', 'desc', 'url']
        );
    }

    /**
     * Muestra el detalle de un tablero (nombre, descripción, URL, última actividad).
     *
     * @param string $boardId ID del tablero
     */
    #[McpTool(name: 'trello_get_board')]
    public function getBoard(string $boardId): array
    {
        return Trim::pick(
            $this->trello->get("boards/$boardId", ['fields' => 'name,desc,url,closed,dateLastActivity']),
            ['id', 'name', 'desc', 'url', 'closed', 'dateLastActivity']
        );
    }

    /**
     * Lista las listas (columnas) abiertas de un tablero. Necesaria para saber el ID de la lista al crear o mover tarjetas.
     *
     * @param string $boardId ID del tablero
     */
    #[McpTool(name: 'trello_get_board_lists')]
    public function getLists(string $boardId): array
    {
        return Trim::each(
            $this->trello->get("boards/$boardId/lists", ['filter' => 'open', 'fields' => 'name,pos']),
            ['id', 'name']
        );
    }

    /**
     * Lista las etiquetas de un tablero con su ID, nombre y color. Úsala antes de etiquetar tarjetas.
     *
     * @param string $boardId ID del tablero
     */
    #[McpTool(name: 'trello_get_board_labels')]
    public function getLabels(string $boardId): array
    {
        return Trim::each($this->trello->get("boards/$boardId/labels", ['fields' => 'name,color']), ['id', 'name', 'color']);
    }

    /**
     * Lista los miembros de un tablero. Úsala para obtener IDs de miembro antes de asignar tarjetas.
     *
     * @param string $boardId ID del tablero
     */
    #[McpTool(name: 'trello_get_board_members')]
    public function getMembers(string $boardId): array
    {
        return Trim::each($this->trello->get("boards/$boardId/members", ['fields' => 'username,fullName']), ['id', 'username', 'fullName']);
    }
}
