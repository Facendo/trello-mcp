<?php

declare(strict_types=1);

namespace McpTrello;

/**
 * Recorta respuestas de Trello a los campos útiles para no inundar el contexto.
 */
final class Trim
{
    /** @param array<string, mixed> $row */
    public static function pick(array $row, array $fields): array
    {
        return array_filter(
            array_intersect_key($row, array_flip($fields)),
            static fn ($v) => $v !== null && $v !== '' && $v !== []
        );
    }

    /** @param list<array<string, mixed>> $rows */
    public static function each(array $rows, array $fields): array
    {
        return array_map(static fn (array $r) => self::pick($r, $fields), $rows);
    }

    /** @param array<string, mixed> $c */
    public static function card(array $c): array
    {
        $out = self::pick($c, ['id', 'name', 'desc', 'due', 'dueComplete', 'closed', 'idList', 'idBoard', 'url', 'dateLastActivity']);
        if (!empty($c['labels'])) {
            $out['labels'] = array_map(
                static fn ($l) => trim(($l['name'] ?? '').' ['.($l['color'] ?? '').']').' ('.$l['id'].')',
                $c['labels']
            );
        }
        if (!empty($c['members'])) {
            $out['members'] = self::each($c['members'], ['id', 'username', 'fullName']);
        } elseif (!empty($c['idMembers'])) {
            $out['idMembers'] = $c['idMembers'];
        }
        if (!empty($c['checklists'])) {
            $out['checklists'] = array_map(static fn ($cl) => [
                'id' => $cl['id'],
                'name' => $cl['name'],
                'items' => array_map(static fn ($i) => self::pick($i, ['id', 'name', 'state']), $cl['checkItems'] ?? []),
            ], $c['checklists']);
        }
        if (!empty($c['attachments'])) {
            $out['attachments'] = self::each($c['attachments'], ['id', 'name', 'url']);
        }

        return $out;
    }
}
