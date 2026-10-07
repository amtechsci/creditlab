<?php
/**
 * Resolve agency name for PG payment links (including legacy rows without agency_name).
 */

function creditlab_pg_link_agency_name_is_placeholder(?string $name, ?string $createdByName = null): bool
{
    $name = trim((string) $name);
    if ($name === '') {
        return true;
    }
    if (strcasecmp($name, 'Agency') === 0) {
        return true;
    }
    $createdByName = trim((string) $createdByName);
    if ($createdByName !== '' && strcasecmp($name, $createdByName) === 0) {
        return true;
    }
    return false;
}

/**
 * SQL expression: best agency label for a pg_payment_link row aliased as pl.
 */
function creditlab_pg_link_agency_name_expr(string $plAlias = 'pl', string $ptAlias = 'pt'): string
{
    return "COALESCE(
        ag_creator.name,
        ag_pl.name,
        IF(
            {$plAlias}.agency_name IS NULL
            OR {$plAlias}.agency_name = ''
            OR {$plAlias}.agency_name = 'Agency'
            OR {$plAlias}.agency_name = {$plAlias}.created_by_name,
            NULL,
            {$plAlias}.agency_name
        ),
        IF(
            {$ptAlias}.agency_name IS NULL
            OR {$ptAlias}.agency_name = ''
            OR {$ptAlias}.agency_name = 'Agency',
            NULL,
            {$ptAlias}.agency_name
        )
    )";
}

/**
 * Hide PG links that belong to a different agency.
 *
 * Uses the same agency label as the profile table. Staff links with no agency
 * stay visible. Call only after creditlab_pg_link_agency_join_sql().
 */
function creditlab_pg_link_sql_hide_other_agencies(int $agencyId, string $plAlias = 'pl', string $ptAlias = 'pt'): string
{
    if ($agencyId <= 0) {
        return ' AND 1=0';
    }
    $agencyId = (int) $agencyId;
    $nameExpr = creditlab_pg_link_agency_name_expr($plAlias, $ptAlias);
    $prefixExpr = "CONCAT('PG_agency_admin_', aa_txn.id, '_')";

    return " AND (
        {$nameExpr} IS NULL
        OR TRIM({$nameExpr}) = ''
        OR LOWER(TRIM({$nameExpr})) = LOWER((SELECT name FROM agency WHERE id = {$agencyId} LIMIT 1))
    )
    AND (aa_creator.id IS NULL OR aa_creator.agency_id = {$agencyId})
    AND ({$plAlias}.agency_id IS NULL OR {$plAlias}.agency_id = 0 OR {$plAlias}.agency_id = {$agencyId})
    AND NOT EXISTS (
        SELECT 1 FROM agency_admin aa_txn
        WHERE aa_txn.agency_id <> {$agencyId}
          AND SUBSTRING({$plAlias}.txnid, 1, CHAR_LENGTH({$prefixExpr})) = {$prefixExpr}
    )";
}

/**
 * Whether an agency portal user may see this payment-link row.
 * Rows with no agency (admin / account manager) stay visible.
 */
function creditlab_pg_link_visible_to_agency(array $row, int $agencyId, string $agencyName = ''): bool
{
    $agencyName = trim($agencyName);
    if ($agencyId <= 0 && $agencyName === '') {
        return false;
    }

    $label = trim((string) ($row['resolved_agency_name'] ?? ''));
    $labeled = $label !== '' && $label !== '—' && strcasecmp($label, 'Agency') !== 0;
    if ($labeled) {
        return $agencyName !== '' && strcasecmp($label, $agencyName) === 0;
    }

    $rowAgencyId = (int) ($row['agency_id'] ?? 0);
    if ($agencyId > 0 && $rowAgencyId > 0) {
        return $rowAgencyId === $agencyId;
    }

    if ($label === '') {
        $label = trim(creditlab_resolve_pg_link_agency_name($row));
        $labeled = $label !== '' && $label !== '—' && strcasecmp($label, 'Agency') !== 0;
        if ($labeled) {
            return $agencyName !== '' && strcasecmp($label, $agencyName) === 0;
        }
    }

    $role = (string) ($row['created_by_role'] ?? '');
    $txnid = (string) ($row['txnid'] ?? '');
    if ($role !== 'agency_admin' && strpos($txnid, 'PG_agency_admin_') !== 0) {
        return true;
    }

    $resolved = creditlab_pg_link_resolve_agency($row);
    if ($resolved === null) {
        return false;
    }
    $resolvedId = (int) ($resolved['agency_id'] ?? 0);
    if ($agencyId > 0 && $resolvedId > 0) {
        return $resolvedId === $agencyId;
    }
    $resolvedName = trim((string) ($resolved['agency_name'] ?? ''));
    return $agencyName !== '' && $resolvedName !== '' && strcasecmp($resolvedName, $agencyName) === 0;
}

/**
 * LEFT JOINs required before using creditlab_pg_link_agency_name_expr().
 */
function creditlab_pg_link_agency_join_sql(string $plAlias = 'pl', string $ptAlias = 'pt'): string
{
    return "
        LEFT JOIN agency ag_pl ON ag_pl.id = {$plAlias}.agency_id
        LEFT JOIN agency_admin aa_creator ON aa_creator.id = {$plAlias}.created_by_id
            AND {$plAlias}.created_by_role = 'agency_admin'
        LEFT JOIN agency ag_creator ON ag_creator.id = aa_creator.agency_id
    ";
}

/**
 * Parse agency_admin id embedded in staff PG txnid (PG_agency_admin_{id}_…).
 */
function creditlab_pg_txnid_agency_admin_id(string $txnid): ?int
{
    if (preg_match('/^PG_agency_admin_(\d+)_/', $txnid, $matches)) {
        $id = (int) $matches[1];
        return $id > 0 ? $id : null;
    }
    return null;
}

/**
 * Resolve agency_id + agency_name for a pg_payment_link row.
 *
 * @return array{agency_id:int,agency_name:string,agency_admin_id?:int}|null
 */
function creditlab_pg_link_resolve_agency(array $row): ?array
{
    $agencyAdminId = (int) ($row['created_by_id'] ?? 0);
    if ($agencyAdminId < 1 && !empty($row['txnid'])) {
        $parsed = creditlab_pg_txnid_agency_admin_id((string) $row['txnid']);
        if ($parsed !== null) {
            $agencyAdminId = $parsed;
        }
    }

    if ($agencyAdminId > 0) {
        $q = towquery(
            "SELECT aa.agency_id, ag.name AS agency_name
            FROM agency_admin aa
            INNER JOIN agency ag ON ag.id = aa.agency_id
            WHERE aa.id={$agencyAdminId}
            LIMIT 1"
        );
        if ($q && townum($q) > 0) {
            $found = towfetch($q);
            if (!empty($found['agency_name'])) {
                return [
                    'agency_id' => (int) $found['agency_id'],
                    'agency_name' => (string) $found['agency_name'],
                    'agency_admin_id' => $agencyAdminId,
                ];
            }
        }
    }

    if (!empty($row['agency_id'])) {
        $q = towquery('SELECT name FROM agency WHERE id=' . (int) $row['agency_id'] . ' LIMIT 1');
        if ($q && townum($q) > 0) {
            $found = towfetch($q);
            if (!empty($found['name'])) {
                return [
                    'agency_id' => (int) $row['agency_id'],
                    'agency_name' => (string) $found['name'],
                ];
            }
        }
    }

    if (!empty($row['id'])) {
        $q = towquery(
            'SELECT paid_via_agency_id, paid_via_agency_name
            FROM loan
            WHERE paid_via_pg_link_id=' . (int) $row['id'] . '
            LIMIT 1'
        );
        if ($q && townum($q) > 0) {
            $found = towfetch($q);
            if (!empty($found['paid_via_agency_name'])) {
                return [
                    'agency_id' => (int) ($found['paid_via_agency_id'] ?? 0),
                    'agency_name' => (string) $found['paid_via_agency_name'],
                ];
            }
        }
    }

    $storedName = trim((string) ($row['agency_name'] ?? ''));
    if ($storedName !== '' && !creditlab_pg_link_agency_name_is_placeholder($storedName, $row['created_by_name'] ?? null)) {
        return [
            'agency_id' => (int) ($row['agency_id'] ?? 0),
            'agency_name' => $storedName,
        ];
    }

    return null;
}

/**
 * Resolve agency name from a pg_payment_link row (+ optional joined columns).
 */
function creditlab_resolve_pg_link_agency_name(array $row): string
{
    if (!empty($row['resolved_agency_name'])) {
        return (string) $row['resolved_agency_name'];
    }

    $resolved = creditlab_pg_link_resolve_agency($row);
    if ($resolved !== null && !empty($resolved['agency_name'])) {
        return (string) $resolved['agency_name'];
    }

    if (($row['created_by_role'] ?? '') !== 'agency_admin' && strpos((string) ($row['txnid'] ?? ''), 'PG_agency_admin_') !== 0) {
        return '—';
    }

    return $row['created_by_name'] ?? 'Agency';
}

/**
 * @return array<int, array<string, mixed>>
 */
function creditlab_pg_link_agency_backfill_candidates(): array
{
    $sql = "SELECT pl.*
            FROM pg_payment_link pl
            WHERE pl.created_by_role = 'agency_admin'
               OR pl.txnid LIKE 'PG_agency_admin_%'
            ORDER BY pl.id ASC";

    $q = towquery($sql);
    if (!$q) {
        return [];
    }

    $rows = [];
    while ($row = towfetch($q)) {
        $resolved = creditlab_pg_link_resolve_agency($row);
        if ($resolved === null) {
            continue;
        }

        $needsUpdate = (int) ($row['agency_id'] ?? 0) !== (int) $resolved['agency_id']
            || creditlab_pg_link_agency_name_is_placeholder($row['agency_name'] ?? null, $row['created_by_name'] ?? null)
            || trim((string) ($row['agency_name'] ?? '')) !== $resolved['agency_name']
            || ((int) ($row['created_by_id'] ?? 0) < 1 && !empty($resolved['agency_admin_id']));

        if (!$needsUpdate) {
            continue;
        }

        $rows[] = [
            'link' => $row,
            'resolved' => $resolved,
        ];
    }

    return $rows;
}

/**
 * Backfill agency_id / agency_name on legacy pg_payment_link + pg_transaction rows.
 *
 * @return array{links:int,transactions:int,candidates:int}
 */
function creditlab_backfill_pg_link_agency_names(bool $apply = false): array
{
    $stats = ['links' => 0, 'transactions' => 0, 'candidates' => 0];
    $candidates = creditlab_pg_link_agency_backfill_candidates();
    $stats['candidates'] = count($candidates);

    foreach ($candidates as $item) {
        $row = $item['link'];
        $resolved = $item['resolved'];
        $agencyId = (int) $resolved['agency_id'];
        $agencyName = towreal($resolved['agency_name']);
        $linkId = (int) $row['id'];
        $txnid = towreal($row['txnid']);
        $stats['links']++;

        if (!$apply) {
            continue;
        }

        $creatorIdSql = '';
        if (!empty($resolved['agency_admin_id']) && (int) ($row['created_by_id'] ?? 0) < 1) {
            $creatorIdSql = ', created_by_id=' . (int) $resolved['agency_admin_id'];
        }

        towquery(
            "UPDATE pg_payment_link
            SET agency_id={$agencyId}, agency_name='{$agencyName}'{$creatorIdSql}
            WHERE id={$linkId}"
        );
        towquery(
            "UPDATE pg_transaction
            SET agency_id={$agencyId}, agency_name='{$agencyName}'
            WHERE txnid='{$txnid}'"
        );
        $stats['transactions']++;
    }

    return $stats;
}
