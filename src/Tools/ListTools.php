<?php

declare(strict_types=1);

namespace McpTrello\Tools;

use Mcp\Server\Capability\Attribute\McpTool;
use McpTrello\Trim;
use McpTrello\TrelloClient;

final class ListTools
{
    public function __construct(private readonly TrelloClient $trello)
    {
    }

    /**
     * Crea una lista (columna) nueva en un tablero.
     *
     * @param string $boardId ID del tablero
     * @param string $name    Nombre de la lista
     * @param string $position "top", "bottom" o un número (por defecto al final)
     */
    #[McpTool(name: 'trello_create_list')]
    public function create(string $boardId, string $name, string $position = 'bottom'): array
    {
        return Trim::pick(
            $this->trello->post('lists', ['idBoard' => $boardId, 'name' => $name, 'pos' => $position]),
            ['id', 'name', 'idBoard']
        );
    }

    /**
     * Renombra una lista.
     *
     * @param string $listId ID de la lista
     * @param string $name   Nuevo nombre
     */
    #[McpTool(name: 'trello_rename_list')]
    public function rename(string $listId, string $name): array
    {
        return Trim::pick($this->trello->put("lists/$listId", ['name' => $name]), ['id', 'name']);
    }

    /**
     * Cambia la posición de una lista dentro de su tablero, o la mueve a otro tablero.
     *
     * @param string      $listId        ID de la lista
     * @param string      $position      "top", "bottom" o un número
     * @param string|null $targetBoardId ID del tablero destino (opcional)
     */
    #[McpTool(name: 'trello_move_list')]
    public function move(string $listId, string $position = 'bottom', ?string $targetBoardId = null): array
    {
        return Trim::pick(
            $this->trello->put("lists/$listId", ['pos' => $position, 'idBoard' => $targetBoardId]),
            ['id', 'name', 'idBoard', 'pos']
        );
    }

    /**
     * Archiva una lista (no se borra; se puede restaurar desde Trello).
     *
     * @param string $listId ID de la lista
     */
    #[McpTool(name: 'trello_archive_list')]
    public function archive(string $listId): array
    {
        return Trim::pick($this->trello->put("lists/$listId", ['closed' => true]), ['id', 'name', 'closed']);
    }
}
