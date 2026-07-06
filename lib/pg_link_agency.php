<?php
/**
 * Resolve agency name for PG payment links (including legacy rows without agency_name).
 */

/**
 * SQL expression: best agency label for a pg_payment_link row aliased as pl.
 */
function creditlab_pg_link_agency_name_expr(string $plAlias = 'pl', string $ptAlias = 'pt'): string
{
    return "COALESCE(
        NULLIF({$plAlias}.agency_name, ''),
        NULLIF({$ptAlias}.agency_name, ''),
        ag_pl.name,
        ag_creator.name
    )";
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
        return (int) $matches[1];
    }
    return null;
}

/**
 * Resolve agency name from a pg_payment_link row (+ optional joined columns).
 */
function creditlab_resolve_pg_link_agency_name(array $row): string
{
    foreach (['resolved_agency_name', 'agency_name', 'pt_agency_name'] as $key) {
        if (!empty($row[$key])) {
            return (string) $row[$key];
        }
    }
    if (!empty($row['ag_pl_name'])) {
        return (string) $row['ag_pl_name'];
    }
    if (!empty($row['ag_creator_name'])) {
        return (string) $row['ag_creator_name'];
    }

    if (($row['created_by_role'] ?? '') !== 'agency_admin') {
        return '—';
    }

    $agencyAdminId = (int) ($row['created_by_id'] ?? 0);
    if ($agencyAdminId < 1 && !empty($row['txnid'])) {
        $parsed = creditlab_pg_txnid_agency_admin_id((string) $row['txnid']);
        if ($parsed !== null) {
            $agencyAdminId = $parsed;
        }
    }
    if ($agencyAdminId > 0) {
        $q = towquery(
            "SELECT ag.name
            FROM agency_admin aa
            INNER JOIN agency ag ON ag.id = aa.agency_id
            WHERE aa.id={$agencyAdminId}
            LIMIT 1"
        );
        if ($q && townum($q) > 0) {
            $found = towfetch($q);
            if (!empty($found['name'])) {
                return (string) $found['name'];
            }
        }
    }

    return $row['created_by_name'] ?? 'Agency';
}

/**
 * Backfill agency_id / agency_name on legacy pg_payment_link + pg_transaction rows.
 *
 * @return array{links:int,transactions:int}
 */
function creditlab_backfill_pg_link_agency_names(bool $apply = false): array
{
    $stats = ['links' => 0, 'transactions' => 0];

    $sql = "SELECT pl.id, pl.txnid, pl.created_by_id, pl.created_by_role, pl.agency_id, pl.agency_name,
                   aa.agency_id AS resolved_agency_id, ag.name AS resolved_agency_name
            FROM pg_payment_link pl
            INNER JOIN agency_admin aa ON aa.id = pl.created_by_id
            INNER JOIN agency ag ON ag.id = aa.agency_id
            WHERE pl.created_by_role = 'agency_admin'
              AND (pl.agency_id IS NULL OR pl.agency_name IS NULL OR pl.agency_name = '')";

    $q = towquery($sql);
    if (!$q) {
        return $stats;
    }

    while ($row = towfetch($q)) {
        $agencyId = (int) $row['resolved_agency_id'];
        $agencyName = towreal($row['resolved_agency_name']);
        $linkId = (int) $row['id'];
        $txnid = towreal($row['txnid']);
        $stats['links']++;

        if ($apply) {
            towquery(
                "UPDATE pg_payment_link
                SET agency_id={$agencyId}, agency_name='{$agencyName}'
                WHERE id={$linkId}"
            );
            towquery(
                "UPDATE pg_transaction
                SET agency_id={$agencyId}, agency_name='{$agencyName}'
                WHERE txnid='{$txnid}'
                  AND (agency_id IS NULL OR agency_name IS NULL OR agency_name = '')"
            );
            $stats['transactions']++;
        }
    }

    return $stats;
}
