<?php
/**
 * Agency: view references only (no edit / SMS).
 */
?>
<table class="table table-bordered profile-inner-table">
    <thead class="thead-light">
        <tr>
            <th>Name</th>
            <th>Phone</th>
            <th>Relation</th>
            <th>Valid</th>
            <th>Contact</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $existing_refs = towquery("SELECT * FROM user_referrals WHERE uid='" . (int) $userpro_id . "' ORDER BY id ASC");
        $has = false;
        while ($ref = towfetch($existing_refs)) {
            $has = true;
            ?>
        <tr>
            <td><?= htmlspecialchars($ref['name'] ?? '') ?></td>
            <td><?= htmlspecialchars($ref['phone'] ?? '') ?></td>
            <td><?= htmlspecialchars($ref['relation'] ?? '') ?></td>
            <td><?= htmlspecialchars($ref['status'] ?? '') ?></td>
            <td>
                <?php if (!empty($ref['phone'])) { ?>
                <a href="http://wa.me/91<?= urlencode((string) $ref['phone']) ?>" target="_blank" rel="noopener" class="btn btn-success btn-sm"><i class="fab fa-whatsapp"></i></a>
                <?php } else {
                    echo '—';
                } ?>
            </td>
        </tr>
            <?php
        }
        if (!$has) {
            echo '<tr><td colspan="5" class="text-muted">No references on file.</td></tr>';
        }
        ?>
    </tbody>
</table>
