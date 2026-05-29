<?php
/**
 * PG link sub-tab — expects $userpro_id, $base_url.
 */
require_once __DIR__ . '/../lib/loan_outstanding.php';
require_once __DIR__ . '/../lib/staff_context.php';

if (!creditlab_can_create_pg_link()) {
    return;
}

$activeLoans = creditlab_active_loans_for_user((int) $userpro_id);
$pgLinksQ = towquery("SELECT * FROM pg_payment_link WHERE uid=" . (int) $userpro_id . " ORDER BY id DESC LIMIT 100");
?>
<div class="product-tab-list tab-pane fade" id="pg_link">
    <div class="review-content-section">
        <h4>Create PG link</h4>
        <div class="row" style="margin-bottom:20px;">
            <div class="col-md-3">
                <label>Loan</label>
                <select id="pg_loan_select" class="form-control">
                    <?php foreach ($activeLoans as $al) {
                        $out = creditlab_loan_outstanding_amount($al);
                        ?>
                    <option value="<?= (int) $al['id'] ?>" data-outstanding="<?= htmlspecialchars((string) $out) ?>">
                        CLL<?= (int) $al['lid'] ?> — ₹<?= number_format($out, 2) ?> (<?= htmlspecialchars($al['status_log']) ?>)
                    </option>
                    <?php } ?>
                </select>
            </div>
            <div class="col-md-3">
                <label>Amount type</label>
                <select id="pg_link_type" class="form-control">
                    <option value="total_outstanding">Total outstanding</option>
                    <option value="manual">Enter amt manually</option>
                </select>
            </div>
            <div class="col-md-3" id="pg_manual_wrap" style="display:none;">
                <label>Amount (₹)</label>
                <input type="number" step="0.01" min="1" id="pg_manual_amount" class="form-control" placeholder="Amount">
            </div>
            <div class="col-md-3">
                <label>&nbsp;</label><br>
                <button type="button" class="btn btn-primary" id="pg_create_btn">Create PG link</button>
            </div>
        </div>
        <p id="pg_outstanding_hint" class="text-muted"></p>
        <p id="pg_create_msg"></p>

        <table class="table table-bordered">
            <thead class="thead-light">
                <tr>
                    <th>S.no</th>
                    <th>Loan ID</th>
                    <th>type</th>
                    <th>amt</th>
                    <th>created by</th>
                    <th>Pg link</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="pg_links_tbody">
                <?php
                $sn = 1;
                if ($pgLinksQ) {
                    while ($pl = towfetch($pgLinksQ)) {
                        $typeLabel = $pl['link_type'] === 'total_outstanding' ? 'total outstanding' : 'manual';
                        $url = $pl['payment_url'] ?? '';
                        ?>
                <tr>
                    <td><?= $sn++ ?></td>
                    <td>CLL<?= (int) $pl['loan_lid'] ?><?php if (!empty($pl['agency_name'])) { ?> <small class="text-muted">(<?= htmlspecialchars($pl['agency_name']) ?>)</small><?php } ?></td>
                    <td><?= htmlspecialchars($typeLabel) ?></td>
                    <td><?= htmlspecialchars($pl['amount']) ?></td>
                    <td><?= htmlspecialchars($pl['created_by_name']) ?></td>
                    <td><?php if ($url) { ?><a href="<?= htmlspecialchars($url) ?>" target="_blank" rel="noopener">Pay link</a><?php } else { ?>—<?php } ?></td>
                    <td><?= htmlspecialchars($pl['status']) ?></td>
                </tr>
                        <?php
                    }
                }
                ?>
            </tbody>
        </table>
    </div>
</div>
<script>
(function() {
    var uid = <?= (int) $userpro_id ?>;
    var typeSel = document.getElementById('pg_link_type');
    var manualWrap = document.getElementById('pg_manual_wrap');
    var loanSel = document.getElementById('pg_loan_select');
    var hint = document.getElementById('pg_outstanding_hint');
    var msg = document.getElementById('pg_create_msg');
    var tbody = document.getElementById('pg_links_tbody');

    function updateHint() {
        if (!loanSel || !loanSel.selectedOptions.length) return;
        var opt = loanSel.selectedOptions[0];
        var out = opt.getAttribute('data-outstanding') || '';
        hint.textContent = typeSel.value === 'total_outstanding'
            ? ('Outstanding for selected loan: ₹' + out)
            : 'Enter partial or full amount manually.';
    }
    if (typeSel) {
        typeSel.addEventListener('change', function() {
            manualWrap.style.display = typeSel.value === 'manual' ? 'block' : 'none';
            updateHint();
        });
    }
    if (loanSel) loanSel.addEventListener('change', updateHint);
    updateHint();

    document.getElementById('pg_create_btn').addEventListener('click', function() {
        msg.textContent = 'Creating…';
        msg.className = '';
        var fd = new FormData();
        fd.append('uid', uid);
        fd.append('loan_internal_id', loanSel.value);
        fd.append('link_type', typeSel.value);
        if (typeSel.value === 'manual') {
            fd.append('manual_amount', document.getElementById('pg_manual_amount').value);
        }
        fetch('/api/create_pg_link.php', { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.ok) {
                    msg.textContent = data.error || 'Failed';
                    msg.className = 'text-danger';
                    return;
                }
                msg.textContent = 'Payment link created.';
                msg.className = 'text-success';
                var row = data.row;
                var tr = document.createElement('tr');
                tr.innerHTML = '<td>' + (tbody.rows.length + 1) + '</td>' +
                    '<td>' + row.loan_id + '</td>' +
                    '<td>' + row.type + '</td>' +
                    '<td>' + row.amount + '</td>' +
                    '<td>' + row.created_by + '</td>' +
                    '<td><a href="' + row.payment_url + '" target="_blank" rel="noopener">Pay link</a></td>' +
                    '<td>' + row.status + '</td>';
                tbody.insertBefore(tr, tbody.firstChild);
            })
            .catch(function() {
                msg.textContent = 'Request failed';
                msg.className = 'text-danger';
            });
    });
})();
</script>
