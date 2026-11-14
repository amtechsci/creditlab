<?php
function creditlab_dash_text(?string $value, string $fallback = '—'): string
{
    $value = trim((string) $value);
    return $value !== '' ? htmlspecialchars($value) : $fallback;
}

function creditlab_currency($amount): string
{
    if ($amount === null || $amount === '') {
        return '₹0.00';
    }
    return '₹' . number_format((float)$amount, 2);
}

function creditlab_status_badge(?string $status, $agreement = 0): string
{
    $raw = trim((string) $status);
    $key = strtolower($raw);
    
    // Special high-notice case: agreement signed by customer
    if ($key === 'disbursal' && (int)$agreement === 1) {
        return '<span class="badge-agreement-signed" style="background-color:#ff3377 !important; color:#ffffff !important; font-weight:800; padding:4px 10px; border-radius:9999px; font-size:11.5px; display:inline-flex; align-items:center; gap:5px; box-shadow:0 2px 6px rgba(255,51,119,0.4);" title="Agreement Signed by Customer - Immediate Disbursal Payout Ready!"><i class="fa-solid fa-file-signature"></i> Disbursal (Signed)</span>';
    }

    $label = $raw !== '' ? $raw : 'Unknown';
    if ($key === 'waiting') {
        $label = 'Just registered';
    } elseif ($key === 'recovery officer') {
        $label = 'Recovery Officer';
    } elseif ($key === 'account manager') {
        $label = 'Account Manager';
    }

    $styles = [
        'default' => ['bg' => '#dc2626', 'fg' => '#ffffff', 'border' => '#b91c1c'],
        'cleared' => ['bg' => '#dcfce7', 'fg' => '#15803d', 'border' => '#bbf7d0'],
        'settled' => ['bg' => '#dcfce7', 'fg' => '#15803d', 'border' => '#bbf7d0'],
        'disbursal' => ['bg' => '#16a34a', 'fg' => '#ffffff', 'border' => '#15803d'],
        'approved' => ['bg' => '#e8f8f4', 'fg' => '#158066', 'border' => '#9ee6d4'],
        'applied' => ['bg' => '#2563eb', 'fg' => '#ffffff', 'border' => '#1d4ed8'],
        'pending' => ['bg' => '#2563eb', 'fg' => '#ffffff', 'border' => '#1d4ed8'],
        'hold' => ['bg' => '#fef3c7', 'fg' => '#b45309', 'border' => '#fde68a'],
        'follow up' => ['bg' => '#fef3c7', 'fg' => '#b45309', 'border' => '#fde68a'],
        'waiting' => ['bg' => '#f1f5f9', 'fg' => '#475569', 'border' => '#e2e8f0'],
        'cancel' => ['bg' => '#ffe4e6', 'fg' => '#be123c', 'border' => '#fecdd3'],
        'account manager' => ['bg' => '#f3e8ff', 'fg' => '#7e22ce', 'border' => '#e9d5ff'],
        'recovery officer' => ['bg' => '#fae8ff', 'fg' => '#86198f', 'border' => '#f5d0fe'],
    ];
    $c = $styles[$key] ?? ['bg' => '#e0f2fe', 'fg' => '#0369a1', 'border' => '#bae6fd'];

    return '<span class="dash-status" style="background:' . $c['bg'] . ';color:' . $c['fg'] . ';border:1px solid ' . $c['border'] . ';font-size:11.5px;font-weight:700;padding:3px 10px;border-radius:9999px;white-space:nowrap;display:inline-flex;align-items:center;line-height:1.4;">'
        . htmlspecialchars($label)
        . '</span>';
}

function creditlab_render_table_legend(): string
{
    return '
    <div class="cl-table-legend-bar">
        <span class="cl-table-legend-title">
            <i class="fa-solid fa-circle-question" style="color:var(--cl-brand); font-size:14px;"></i>
            <span>Quick Guide:</span>
        </span>
        <span class="cl-legend-item" title="Returning borrower who previously had a special loan">
            <strong class="cl-symbol cl-symbol-sloan">@</strong> Special / Repeat Borrower
        </span>
        <span class="cl-legend-item" title="Borrower currently has an active ongoing loan">
            <strong class="cl-symbol cl-symbol-loan">#</strong> Existing Active Loan
        </span>
        <span class="cl-legend-item" title="All mandatory KYC & income documents uploaded">
            <strong class="cl-symbol cl-symbol-docs">✔</strong> All Docs Complete
        </span>
        <span class="cl-legend-item" title="Customer accepted and signed loan agreement - transfer money immediately!">
            <span class="cl-legend-pill cl-legend-pink"><i class="fa-solid fa-file-signature"></i> Pink</span> Agreement Signed (Payout Ready)
        </span>
        <span class="cl-legend-item" title="Disbursal approved - awaiting customer signature">
            <span class="cl-legend-pill cl-legend-green">Green</span> Approved
        </span>
        <span class="cl-legend-item" title="Disbursal unsigned and bank not yet verified">
            <span class="cl-legend-pill" style="background:#000;color:#fff;">Black</span> Bank pending
        </span>
        <span class="cl-legend-item" title="Defaulted or high risk">
            <span class="cl-legend-pill cl-legend-red">Red</span> Default
        </span>
        <button type="button" onclick="window.clOpenGuide && window.clOpenGuide()" style="margin-left:auto; background:none; border:none; color:var(--cl-brand); font-weight:700; font-size:12px; cursor:pointer; padding:2px 6px; text-decoration:underline;">
            Full Legend & Guide &raquo;
        </button>
    </div>';
}

function creditlab_render_help_modal(): string
{
    return '
    <div class="cl-guide-overlay" id="clGuideOverlay" onclick="window.clCloseGuide && window.clCloseGuide()"></div>
    <div class="cl-guide-drawer" id="clGuideDrawer">
        <div class="cl-guide-header">
            <h3><i class="fa-solid fa-circle-question" style="color:var(--cl-brand);"></i> CreditLab Admin Guide & Legend</h3>
            <button type="button" class="cl-guide-close" id="clGuideCloseBtn" onclick="window.clCloseGuide && window.clCloseGuide()">&times;</button>
        </div>
        <div class="cl-guide-body">
            <!-- SECTION 1: ACTION COLORS -->
            <div class="cl-guide-section">
                <div class="cl-guide-section-title"><i class="fa-solid fa-palette"></i> Table Color Highlights</div>
                
                <div class="cl-guide-card">
                    <div class="cl-guide-card-icon" style="background:#ff3377; color:#fff;">🌸</div>
                    <div class="cl-guide-card-content">
                        <h4>Pink (#FF3377 / #FFC0CB) — Agreement Signed</h4>
                        <p><strong>Highest Priority Action!</strong> The customer has digitally accepted and signed the loan agreement. Disbursal team should verify bank details and transfer funds immediately.</p>
                    </div>
                </div>

                <div class="cl-guide-card">
                    <div class="cl-guide-card-icon" style="background:#16a34a; color:#fff;">🟢</div>
                    <div class="cl-guide-card-content">
                        <h4>Green (#16A34A) — Disbursal Approved</h4>
                        <p>Loan is approved by underwriting and waiting for the customer to review and sign their digital agreement on the portal.</p>
                    </div>
                </div>

                <div class="cl-guide-card">
                    <div class="cl-guide-card-icon" style="background:#dc2626; color:#fff;">🔴</div>
                    <div class="cl-guide-card-content">
                        <h4>Red (#DC2626) — Default / Critical Alert</h4>
                        <p>Customer has defaulted or is severely overdue. Immediate collections or recovery assignment is required.</p>
                    </div>
                </div>

                <div class="cl-guide-card">
                    <div class="cl-guide-card-icon" style="background:#2563eb; color:#fff;">🔵</div>
                    <div class="cl-guide-card-content">
                        <h4>Blue (#2563EB) — Underwriting / In Review</h4>
                        <p>Standard applied or in-review status. Underwriter should verify documents, CIBIL, and banking statement.</p>
                    </div>
                </div>
            </div>

            <!-- SECTION 2: NAME SYMBOLS -->
            <div class="cl-guide-section">
                <div class="cl-guide-section-title"><i class="fa-solid fa-icons"></i> Customer Name Indicators</div>
                
                <div class="cl-guide-card">
                    <div class="cl-guide-card-icon" style="background:#fee2e2; color:#dc2626; font-weight:900;">@</div>
                    <div class="cl-guide-card-content">
                        <h4>Red @ — Special / Repeat Customer (sloan &gt; 0)</h4>
                        <p>Customer has completed previous loans with CreditLab and qualified for repeat/special loan status. Eligible for faster processing.</p>
                    </div>
                </div>

                <div class="cl-guide-card">
                    <div class="cl-guide-card-icon" style="background:#fee2e2; color:#dc2626; font-weight:900;">#</div>
                    <div class="cl-guide-card-content">
                        <h4>Red # — Has Active Ongoing Loan (loan &gt; 0)</h4>
                        <p>Borrower already has an active loan in progress. Check repayment track before issuing additional credit.</p>
                    </div>
                </div>

                <div class="cl-guide-card">
                    <div class="cl-guide-card-icon" style="background:#dcfce7; color:#16a34a; font-weight:900;">✔</div>
                    <div class="cl-guide-card-content">
                        <h4>Green ✔ — All 6 Documents Uploaded</h4>
                        <p>Customer has uploaded all required documentation: Aadhaar, PAN, Salary Slips, Bank Statement, Company ID, and Address Proof.</p>
                    </div>
                </div>

                <div class="cl-guide-card">
                    <div class="cl-guide-card-icon" style="background:#f1f5f9; color:#475569; font-weight:900;">+/-</div>
                    <div class="cl-guide-card-content">
                        <h4>Green + / Red - — Verification Status</h4>
                        <p><strong>+</strong> indicates contact/KYC verification approved; <strong>-</strong> indicates verification rejected or pending clarification.</p>
                    </div>
                </div>
            </div>

            <!-- SECTION 3: KEY ACTIONS -->
            <div class="cl-guide-section">
                <div class="cl-guide-section-title"><i class="fa-solid fa-bolt"></i> Quick Actions</div>
                <div class="cl-guide-card">
                    <div class="cl-guide-card-icon" style="background:var(--cl-brand-light); color:var(--cl-brand);">🔍</div>
                    <div class="cl-guide-card-content">
                        <h4>View Profile</h4>
                        <p>Access full customer dossier, all previous loans, CIBIL/PAN/Bank statement analytics, SMS/email logs, and payment gateway link generator.</p>
                    </div>
                </div>
                <div class="cl-guide-card">
                    <div class="cl-guide-card-icon" style="background:#eff6ff; color:#2563eb;">📥</div>
                    <div class="cl-guide-card-content">
                        <h4>Download Disbursal File</h4>
                        <p>Generates the banking payout batch file for instantaneous NEFT/IMPS processing.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
    window.clOpenGuide = function() {
        var overlay = document.getElementById("clGuideOverlay");
        var drawer = document.getElementById("clGuideDrawer");
        if (overlay) overlay.classList.add("active");
        if (drawer) drawer.classList.add("active");
    };
    window.clCloseGuide = function() {
        var overlay = document.getElementById("clGuideOverlay");
        var drawer = document.getElementById("clGuideDrawer");
        if (overlay) overlay.classList.remove("active");
        if (drawer) drawer.classList.remove("active");
    };
    document.addEventListener("DOMContentLoaded", function() {
        var btn = document.getElementById("clHelpBtn");
        if (btn) {
            btn.addEventListener("click", function(e) {
                e.preventDefault();
                window.clOpenGuide();
            });
        }
    });
    </script>
    ';
}

function creditlab_render_pagination(int $pageno, int $total_pages, string $url_prefix = '?pageno='): string
{
    if ($total_pages <= 1) {
        return '';
    }
    
    $prev_url = $pageno <= 1 ? '#' : $url_prefix . ($pageno - 1);
    $next_url = $pageno >= $total_pages ? '#' : $url_prefix . ($pageno + 1);

    $html = '<nav aria-label="Page navigation"><ul class="pagination" style="margin:0;">';
    
    // Previous
    $html .= '<li class="page-item ' . ($pageno <= 1 ? 'disabled' : '') . '">';
    $html .= '<a class="page-link" href="' . $prev_url . '" aria-label="Previous">&laquo;</a></li>';

    // Window logic
    $win_start = max(1, $pageno - 2);
    $win_end = min($total_pages, $pageno + 2);

    if ($win_start > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $url_prefix . '1">1</a></li>';
        if ($win_start > 2) {
            $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>';
        }
    }

    for ($i = $win_start; $i <= $win_end; $i++) {
        $active = $i == $pageno ? ' active' : '';
        $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . $url_prefix . $i . '">' . $i . '</a></li>';
    }

    if ($win_end < $total_pages) {
        if ($win_end < $total_pages - 1) {
            $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>';
        }
        $html .= '<li class="page-item"><a class="page-link" href="' . $url_prefix . $total_pages . '">' . $total_pages . '</a></li>';
    }

    // Next
    $html .= '<li class="page-item ' . ($pageno >= $total_pages ? 'disabled' : '') . '">';
    $html .= '<a class="page-link" href="' . $next_url . '" aria-label="Next">&raquo;</a></li>';

    $html .= '</ul></nav>';
    return $html;
}
