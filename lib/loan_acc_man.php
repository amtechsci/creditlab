<?php
/**
 * Account manager follow-up updates (loan_acc_man).
 */

/**
 * SQL fragment restricting rows to updates made by admins of one agency.
 */
function creditlab_loan_acc_man_sql_agency_filter(int $agencyId): string
{
    if ($agencyId <= 0) {
        return ' AND 1=0';
    }

    $names = [];
    $q = towquery(
        "SELECT name FROM agency_admin WHERE agency_id = " . (int) $agencyId . " AND active = 1"
    );
    if ($q) {
        while ($row = towfetch($q)) {
            $name = trim($row['name'] ?? '');
            if ($name !== '') {
                $names[] = "'" . towreal($name) . "'";
            }
        }
    }

    if ($names === []) {
        return ' AND 1=0';
    }

    return ' AND updated_by IN (' . implode(',', array_unique($names)) . ')';
}

/**
 * Remove note/follow-up lines written by another agency.
 * Staff lines (no agency admin name) and this agency's own lines stay.
 */
function creditlab_hide_other_agency_log(?string $html, int $agencyId): string
{
    $html = (string) $html;
    if ($html === '' || $agencyId <= 0) {
        return '';
    }

    $own = [];
    $other = [];
    $q = towquery('SELECT agency_id, name FROM agency_admin');
    if ($q) {
        while ($row = towfetch($q)) {
            $name = strtolower(trim((string) ($row['name'] ?? '')));
            if ($name === '') {
                continue;
            }
            if ((int) $row['agency_id'] === $agencyId) {
                $own[$name] = true;
            } else {
                $other[$name] = true;
            }
        }
    }

    $parts = preg_split('/<br\s*\/?>/i', $html);
    if ($parts === false) {
        return '';
    }

    $kept = [];
    foreach ($parts as $part) {
        if (trim(strip_tags($part)) === '') {
            continue;
        }
        if (preg_match('/Updated by\s*<b[^>]*>\s*([^<]+?)\s*<\/b>/i', $part, $matches)) {
            $name = strtolower(trim(html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8')));
            if ($name !== '' && isset($other[$name]) && !isset($own[$name])) {
                continue;
            }
        }
        $kept[] = $part;
    }

    return $kept === [] ? '' : implode('<br>', $kept);
}

/**
 * Latest follow-up rows for a loan, optionally scoped to one agency.
 *
 * @return array{responses: list<string|null>, commit_dates: list<string|null>, updated_ats: list<string|null>}
 */
function creditlab_loan_acc_man_recent_rows(int $lid, ?int $agencyId = null, int $limit = 3): array
{
    $filter = ($agencyId !== null && $agencyId > 0)
        ? creditlab_loan_acc_man_sql_agency_filter($agencyId)
        : '';
    $limit = max(1, (int) $limit);

    $responses = [];
    $commit_dates = [];
    $updated_ats = [];

    $q = towquery(
        'SELECT customer_response, commitment_date, updated_at FROM `loan_acc_man`'
        . ' WHERE lid=' . (int) $lid . $filter
        . ' ORDER BY id DESC LIMIT ' . $limit
    );
    if ($q) {
        while ($row = towfetch($q)) {
            $responses[] = $row['customer_response'];
            $commit_dates[] = $row['commitment_date'];
            $updated_ats[] = $row['updated_at'];
        }
    }

    return [
        'responses' => $responses,
        'commit_dates' => $commit_dates,
        'updated_ats' => $updated_ats,
    ];
}

/**
 * loan_apply.id values for a user currently in account-manager collections.
 *
 * @return int[]
 */
function creditlab_account_manager_loan_ids_for_user(int $uid): array
{
    $uid = (int) $uid;
    if ($uid <= 0) {
        return [];
    }
    $ids = [];
    $q = towquery(
        "SELECT id FROM loan_apply WHERE uid={$uid} AND status='account manager' ORDER BY id DESC"
    );
    if ($q) {
        while ($row = towfetch($q)) {
            $ids[] = (int) $row['id'];
        }
    }
    return $ids;
}

/**
 * Default loan id for Account Manager follow-up form (account_manager / agency staff).
 */
function creditlab_default_account_manager_loan_id(int $uid, ?int $hintLid = null): ?int
{
    $ids = creditlab_account_manager_loan_ids_for_user($uid);
    if ($ids === []) {
        return null;
    }
    if ($hintLid !== null && $hintLid > 0 && in_array($hintLid, $ids, true)) {
        return $hintLid;
    }
    $q = towquery(
        "SELECT la.id FROM loan_apply la
        INNER JOIN loan l ON l.lid = la.id
        WHERE la.uid=" . (int) $uid . "
        AND la.status='account manager'
        AND l.status_log='account manager'
        ORDER BY la.id DESC
        LIMIT 1"
    );
    if ($q && townum($q) > 0) {
        $row = towfetch($q);
        return (int) $row['id'];
    }
    return $ids[0];
}
