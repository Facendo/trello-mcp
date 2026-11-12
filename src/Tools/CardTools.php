<?php

declare(strict_types=1);

namespace McpTrello\Tools;

use Mcp\Server\Capability\Attribute\McpTool;
use McpTrello\Trim;
use McpTrello\TrelloClient;

final class CardTools
{
    public function __construct(private readonly TrelloClient $trello)
    {
    }

    /**
     * Lista las tarjetas abiertas de una lista, en formato resumido.
     *
     * @param string $listId ID de la lista
     */
    #[McpTool(name: 'trello_get_list_cards')]
    public function listCards(string $listId): array
    {
        $cards = $this->trello->get("lists/$listId/cards", ['fields' => 'name,due,dueComplete,idMembers,labels,url']);

        return array_map(Trim::card(...), $cards);
    }

    /**
     * Muestra una tarjeta completa: descripción, fechas, etiquetas, miembros, checklists y adjuntos.
     *
     * @param string $cardId ID de la tarjeta
     */
    #[McpTool(name: 'trello_get_card')]
    public function get(string $cardId): array
    {
        return Trim::card($this->trello->get("cards/$cardId", [
            'fields' => 'name,desc,due,dueComplete,closed,idList,idBoard,url,labels,dateLastActivity',
            'members' => 'true',
            'member_fields' => 'username,fullName',
            'checklists' => 'all',
            'attachments' => 'true',
        ]));
    }

    /**
     * Crea una tarjeta en una lista.
     *
     * @param string        $listId    ID de la lista donde crearla
     * @param string        $name      Título de la tarjeta
     * @param string|null   $desc      Descripción (admite Markdown)
     * @param string|null   $due       Fecha límite en ISO 8601, p. ej. 2026-12-31T18:00:00Z
     * @param string[]|null $memberIds IDs de miembros a asignar
     * @param string[]|null $labelIds  IDs de etiquetas
     * @param string        $position  "top" o "bottom"
     */
    #[McpTool(name: 'trello_create_card')]
    public function create(
        string $listId,
        string $name,
        ?string $desc = null,
        ?string $due = null,
        ?array $memberIds = null,
        ?array $labelIds = null,
        string $position = 'bottom',
    ): array {
        return Trim::card($this->trello->post('cards', [
            'idList' => $listId, 'name' => $name, 'desc' => $desc, 'due' => $due,
            'idMembers' => $memberIds, 'idLabels' => $labelIds, 'pos' => $position,
        ]));
    }

    /**
     * Edita una tarjeta. Solo se modifican los campos enviados. Para quitar la fecha límite envía due="null".
     *
     * @param string      $cardId      ID de la tarjeta
     * @param string|null $name        Nuevo título
     * @param string|null $desc        Nueva descripción
     * @param string|null $due         Nueva fecha límite ISO 8601 (o "null" para quitarla)
     * @param bool|null   $dueComplete Marcar la fecha límite como cumplida
     */
    #[McpTool(name: 'trello_update_card')]
    public function update(string $cardId, ?string $name = null, ?string $desc = null, ?string $due = null, ?bool $dueComplete = null): array
    {
        return Trim::card($this->trello->put("cards/$cardId", [
            'name' => $name, 'desc' => $desc, 'due' => $due, 'dueComplete' => $dueComplete,
        ]));
    }

    /**
     * Mueve una tarjeta a otra lista (por ejemplo, de "Por hacer" a "Hecho"), y opcionalmente a otro tablero.
     *
     * @param string      $cardId        ID de la tarjeta
     * @param string      $targetListId  ID de la lista destino
     * @param string      $position      "top" o "bottom"
     * @param string|null $targetBoardId ID del tablero destino si cambia de tablero
     */
    #[McpTool(name: 'trello_move_card')]
    public function move(string $cardId, string $targetListId, string $position = 'bottom', ?string $targetBoardId = null): array
    {
        return Trim::card($this->trello->put("cards/$cardId", [
            'idList' => $targetListId, 'pos' => $position, 'idBoard' => $targetBoardId,
        ]));
    }

    /**
     * Asigna un miembro a una tarjeta.
     *
     * @param string $cardId   ID de la tarjeta
     * @param string $memberId ID del miembro (ver trello_get_board_members)
     */
    #[McpTool(name: 'trello_add_card_member')]
    public function addMember(string $cardId, string $memberId): array
    {
        $this->trello->post("cards/$cardId/idMembers", ['value' => $memberId]);

        return ['ok' => true, 'cardId' => $cardId, 'memberId' => $memberId];
    }

    /**
     * Añade una etiqueta existente a una tarjeta.
     *
     * @param string $cardId  ID de la tarjeta
     * @param string $labelId ID de la etiqueta (ver trello_get_board_labels)
     */
    #[McpTool(name: 'trello_add_card_label')]
    public function addLabel(string $cardId, string $labelId): array
    {
        $this->trello->post("cards/$cardId/idLabels", ['value' => $labelId]);

        return ['ok' => true, 'cardId' => $cardId, 'labelId' => $labelId];
    }

    /**
     * Archiva una tarjeta (no se borra; se puede restaurar desde Trello).
     *
     * @param string $cardId ID de la tarjeta
     */
    #[McpTool(name: 'trello_archive_card')]
    public function archive(string $cardId): array
    {
        return Trim::pick($this->trello->put("cards/$cardId", ['closed' => true]), ['id', 'name', 'closed']);
    }
}
