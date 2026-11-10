<?php
/**
 * Agency: read-only personal fields for collections (no PII / internal underwriting data).
 */
?>
<p class="text-muted" style="margin-bottom:16px;font-size:13px;">
    View only — contact and employment details for recovery. Profile changes are not available in the agency portal.
</p>
<div class="row">
    <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
        <div class="form-group">
            <label>Name</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($userpro_name ?? '') ?>" readonly disabled>
        </div>
        <div class="form-group">
            <label>Mobile</label>
            <div class="input-group">
                <input type="text" class="form-control" value="<?= htmlspecialchars($userpro_mobile ?? '') ?>" readonly disabled>
                <?php if (!empty($userpro_mobile)) { ?>
                <div class="input-group-append">
                    <a href="http://wa.me/91<?= urlencode((string) $userpro_mobile) ?>" target="_blank" rel="noopener" class="btn btn-success"><i class="fab fa-whatsapp"></i></a>
                </div>
                <?php } ?>
            </div>
        </div>
        <div class="form-group">
            <label>Alternate mobile</label>
            <div class="input-group">
                <input type="text" class="form-control" value="<?= htmlspecialchars(($userpro_altmobile ?? '') !== '' && ($userpro_altmobile ?? '') !== '0' ? (string) $userpro_altmobile : '—') ?>" readonly disabled>
                <?php if (!empty($userpro_altmobile) && $userpro_altmobile !== '0') { ?>
                <div class="input-group-append">
                    <a href="http://wa.me/91<?= urlencode((string) $userpro_altmobile) ?>" target="_blank" rel="noopener" class="btn btn-success"><i class="fab fa-whatsapp"></i></a>
                </div>
                <?php } ?>
            </div>
        </div>
        <div class="form-group">
            <label>Company</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($userpro_company ?? '') ?>" readonly disabled>
        </div>
        <div class="form-group">
            <label>Designation</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($userpro_designation ?? '') ?>" readonly disabled>
        </div>
    </div>
    <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
        <div class="form-group">
            <label>City (job location)</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($userpro_state ?? '') ?>" readonly disabled>
        </div>
        <?php if ($agency_customer_dpd !== null) { ?>
        <div class="form-group">
            <label>Days past due (active case)</label>
            <input type="text" class="form-control" value="<?= (int) $agency_customer_dpd ?>" readonly disabled>
        </div>
        <?php } ?>
    </div>
</div>
