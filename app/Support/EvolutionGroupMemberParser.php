<?php

namespace App\Support;

class EvolutionGroupMemberParser
{
    /**
     * @return array<int, array{id: string, phone: string, phone_jid: string, is_admin: bool, role: string}>
     */
    public static function parse(mixed $payload): array
    {
        $participants = self::extractParticipants($payload);
        $members = [];

        foreach ($participants as $participant) {
            if (! is_array($participant)) {
                continue;
            }

            $phoneJid = (string) ($participant['phoneNumber'] ?? $participant['id'] ?? '');
            $phone = self::extractPhone($phoneJid);
            $admin = $participant['admin'] ?? null;
            $isAdmin = $admin === 'admin' || $admin === 'superadmin' || $admin === true;

            $members[] = [
                'id' => (string) ($participant['id'] ?? $phoneJid),
                'phone' => $phone,
                'phone_jid' => $phoneJid,
                'is_admin' => $isAdmin,
                'role' => $isAdmin ? (string) ($admin === 'superadmin' ? 'superadmin' : 'admin') : 'member',
            ];
        }

        usort($members, fn (array $a, array $b) => [$b['is_admin'], $a['phone']] <=> [$a['is_admin'], $b['phone']]);

        return $members;
    }

    /**
     * @return array<string, mixed>
     */
    public static function summarizeGroup(mixed $group, string $groupJid): array
    {
        if (! is_array($group)) {
            return [
                'jid' => $groupJid,
                'name' => '—',
                'size' => 0,
                'owner' => '—',
                'created_at' => null,
                'description' => null,
                'is_announce' => false,
                'is_restricted' => false,
            ];
        }

        $creation = $group['creation'] ?? null;

        return [
            'jid' => $group['id'] ?? $group['jid'] ?? $groupJid,
            'name' => $group['subject'] ?? $group['name'] ?? '—',
            'size' => (int) ($group['size'] ?? count($group['participants'] ?? [])),
            'owner' => self::extractPhone((string) ($group['owner'] ?? '')),
            'created_at' => is_numeric($creation) ? (int) $creation : null,
            'description' => $group['desc'] ?? $group['description'] ?? null,
            'is_announce' => ! empty($group['announce']),
            'is_restricted' => ! empty($group['restrict']),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private static function extractParticipants(mixed $payload): array
    {
        if (! is_array($payload)) {
            return [];
        }

        if (isset($payload['participants']) && is_array($payload['participants'])) {
            return $payload['participants'];
        }

        if (array_is_list($payload)) {
            return $payload;
        }

        return [];
    }

    public static function extractPhone(string $jid): string
    {
        if ($jid === '') {
            return '';
        }

        $jid = explode('@', $jid)[0] ?? $jid;

        return preg_replace('/\D+/', '', $jid) ?: $jid;
    }
}
