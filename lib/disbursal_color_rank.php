<?php
/**
 * Disbursal / Repeat Loan D status color ranking for page ordering.
 *
 * Pink  (1): disbursal + agreement signed
 * Black (2): disbursal + unsigned + latest user_bank.verify = 0
 * Blue  (3): any other status
 * Green (4): disbursal + unsigned + bank verified
 */

function creditlab_disbursal_bank_verify(int $uid): int
{
    static $cache = [];
    if (array_key_exists($uid, $cache)) {
        return $cache[$uid];
    }
    $row = towfetch(towquery("SELECT verify FROM `user_bank` WHERE uid='" . intval($uid) . "' ORDER BY `id` DESC LIMIT 1"));
    $cache[$uid] = isset($row['verify']) ? (int) $row['verify'] : 1;
    return $cache[$uid];
}

/**
 * @return int 1=pink, 2=black, 3=blue, 4=green
 */
function creditlab_disbursal_color_rank(string $userStatus, $agreement, int $uid): int
{
    $status = strtolower(trim($userStatus));
    $signed = (int) $agreement === 1;
    if ($status === 'disbursal' && $signed) {
        return 1; // pink
    }
    if ($status === 'disbursal') {
        $verify = creditlab_disbursal_bank_verify($uid);
        if ($verify === 0) {
            return 2; // black
        }
        return 4; // green
    }
    return 3; // blue
}

function creditlab_disbursal_status_badge_html(string $userStatus, $agreement, int $uid): string
{
    $rank = creditlab_disbursal_color_rank($userStatus, $agreement, $uid);
    if ($rank === 1) {
        return '<span class="badge-modern badge-signed" style="background:#ff3377 !important; color:#ffffff !important; font-weight:700; padding:6px 12px; border-radius:12px; box-shadow:0 2px 6px rgba(255,51,119,0.3); display:inline-flex; align-items:center; gap:4px;"><i class="fa-solid fa-file-signature"></i> Signed (Disburse)</span>';
    }
    if ($rank === 2) {
        return '<span class="dash-status" style="background:#000000;color:#ffffff;border:1px solid #111;font-size:11.5px;font-weight:700;padding:3px 10px;border-radius:9999px;white-space:nowrap;display:inline-flex;align-items:center;line-height:1.4;">Bank pending</span>';
    }
    if ($rank === 4) {
        return '<span class="dash-status" style="background:#16a34a;color:#ffffff;border:1px solid #15803d;font-size:11.5px;font-weight:700;padding:3px 10px;border-radius:9999px;white-space:nowrap;display:inline-flex;align-items:center;line-height:1.4;">Disbursal</span>';
    }
    return creditlab_status_badge($userStatus, $agreement);
}

/**
 * Load full disbursal/repeat rows, attach user + color rank, sort, then page-slice.
 *
 * @param string $sloanFilter 'zero' (sloan=0) | 'repeat' (sloan>0)
 * @param string $pinkPlacement 'top' (repeat: pink first) | 'bottom' (disbursal: pink last)
 * @return array{rows:array,total_rows:int}
 */
function creditlab_disbursal_sorted_page(string $sloanFilter, string $pinkPlacement, int $offset, int $perPage): array
{
    if ($sloanFilter === 'repeat') {
        $where = "loan_apply.status='disbursal' AND user.sloan > 0";
    } else {
        $where = "loan_apply.status='disbursal' AND user.sloan = 0";
    }

    $sql = "SELECT loan_apply.*, user.sloan AS join_sloan
            FROM loan_apply
            LEFT JOIN user ON loan_apply.uid = user.id
            WHERE $where
            ORDER BY user.sloan DESC, loan_apply.id DESC";
    $result = towquery($sql);
    $all = [];
    while ($loanfetch = towfetch($result)) {
        $uid = (int) $loanfetch['uid'];
        $userRow = towfetch(towquery('SELECT * FROM user WHERE id=' . $uid));
        if (!$userRow) {
            continue;
        }
        $userStatus = (string) ($userRow['status'] ?? '');
        $agreement = $loanfetch['agreement'] ?? 0;
        $rank = creditlab_disbursal_color_rank($userStatus, $agreement, $uid);
        $loanfetch['_user'] = $userRow;
        $loanfetch['_color_rank'] = $rank;
        $all[] = $loanfetch;
    }

    if ($pinkPlacement === 'bottom') {
        // Keep SQL order; move pink (signed) rows to the end so they land on the last page.
        $nonPink = [];
        $pink = [];
        foreach ($all as $row) {
            if ((int) $row['_color_rank'] === 1) {
                $pink[] = $row;
            } else {
                $nonPink[] = $row;
            }
        }
        $all = array_merge($nonPink, $pink);
    } else {
        // Repeat: pink(1), black(2), blue(3), green(4); within group keep sloan DESC then id DESC.
        usort($all, static function ($a, $b) {
            $ra = (int) $a['_color_rank'];
            $rb = (int) $b['_color_rank'];
            if ($ra !== $rb) {
                return $ra <=> $rb;
            }
            $sa = (int) ($a['join_sloan'] ?? $a['sloan'] ?? 0);
            $sb = (int) ($b['join_sloan'] ?? $b['sloan'] ?? 0);
            if ($sa !== $sb) {
                return $sb <=> $sa;
            }
            return ((int) $b['id']) <=> ((int) $a['id']);
        });
    }

    $total = count($all);
    $page = array_slice($all, $offset, $perPage);
    return ['rows' => $page, 'total_rows' => $total];
}
