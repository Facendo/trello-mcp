<?php

declare(strict_types=1);

namespace McpTrello\Tools;

use Mcp\Server\Capability\Attribute\McpTool;
use McpTrello\Trim;
use McpTrello\TrelloClient;

final class CollaborationTools
{
    public function __construct(private readonly TrelloClient $trello)
    {
    }

    /**
     * Añade un comentario a una tarjeta.
     *
     * @param string $cardId ID de la tarjeta
     * @param string $text   Texto del comentario (admite Markdown)
     */
    #[McpTool(name: 'trello_add_comment')]
    public function comment(string $cardId, string $text): array
    {
        $r = $this->trello->post("cards/$cardId/actions/comments", ['text' => $text]);

        return ['ok' => true, 'commentId' => $r['id'] ?? null];
    }

    /**
     * Crea una checklist en una tarjeta, con elementos opcionales.
     *
     * @param string        $cardId ID de la tarjeta
     * @param string        $name   Nombre de la checklist
     * @param string[]|null $items  Textos de los elementos iniciales
     */
    #[McpTool(name: 'trello_create_checklist')]
    public function createChecklist(string $cardId, string $name, ?array $items = null): array
    {
        $checklist = $this->trello->post("cards/$cardId/checklists", ['name' => $name]);
        $created = [];
        foreach ($items ?? [] as $item) {
            $created[] = Trim::pick(
                $this->trello->post("checklists/{$checklist['id']}/checkItems", ['name' => $item]),
                ['id', 'name', 'state']
            );
        }

        return ['id' => $checklist['id'], 'name' => $checklist['name'], 'items' => $created];
    }

    /**
     * Añade un elemento a una checklist existente.
     *
     * @param string $checklistId ID de la checklist (ver trello_get_card)
     * @param string $name        Texto del elemento
     */
    #[McpTool(name: 'trello_add_checklist_item')]
    public function addItem(string $checklistId, string $name): array
    {
        return Trim::pick($this->trello->post("checklists/$checklistId/checkItems", ['name' => $name]), ['id', 'name', 'state']);
    }

    /**
     * Marca un elemento de checklist como completado o pendiente.
     *
     * @param string $cardId      ID de la tarjeta
     * @param string $checkItemId ID del elemento (ver trello_get_card)
     * @param bool   $complete    true = completado, false = pendiente
     */
    #[McpTool(name: 'trello_set_checklist_item_state')]
    public function setItemState(string $cardId, string $checkItemId, bool $complete = true): array
    {
        return Trim::pick(
            $this->trello->put("cards/$cardId/checkItem/$checkItemId", ['state' => $complete ? 'complete' : 'incomplete']),
            ['id', 'name', 'state']
        );
    }

    /**
     * Adjunta un enlace (URL) a una tarjeta.
     *
     * @param string      $cardId ID de la tarjeta
     * @param string      $url    URL a adjuntar
     * @param string|null $name   Texto a mostrar para el enlace
     */
    #[McpTool(name: 'trello_attach_link')]
    public function attachLink(string $cardId, string $url, ?string $name = null): array
    {
        return Trim::pick($this->trello->post("cards/$cardId/attachments", ['url' => $url, 'name' => $name]), ['id', 'name', 'url']);
    }
}
