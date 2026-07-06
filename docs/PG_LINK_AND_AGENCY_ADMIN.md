# PG payment links & agency admin

## Database setup (run once on XAMPP/MySQL)

```bash
cd /var/www/creditlab.in
sudo -u www-data php migrations/20260529_pg_links_and_agency_admin.php
```

Use `www-data` if `.env` is not readable by your SSH user. Ensure `/var/www/creditlab.in/.env` has correct `DB_HOST`, `DB_USER`, `DB_PASSWORD`, `DB_NAME`.

Or import manually: `sql/20260529_pg_links_and_agency_admin.sql`

## Easebuzz (.env)

```
EASEBUZZ_MERCHANT_KEY=...
EASEBUZZ_SALT=...
EASEBUZZ_ENV=prod
```

## Admin: create agencies

1. Log in as admin → **Agency admins** in left menu (`/admin/agency_admins.php`)
2. Create an **agency** name
3. Create **agency admin** (email + password)

## PG link tab

Available on customer profile for:

- Admin (`/admin/profile.php?id=…&tab=pg_link`)
- Account manager
- Agency admin

**Create PG link**: choose loan, total outstanding or manual amount, then copy the Easebuzz pay URL.

Payments settle via `easebuzz_webhook.php` and `payeasebuzz/response.php`:

- **Total outstanding** (agency or staff) → loan cleared when paid in full
- **Manual (agency admin)** → payment recorded only; loan stays open; appears in **Agency wise payments** CSV for manual review (no `transaction_details`, no auto-clear, no NOC/SMS)
- **Manual (admin / account manager)** → part payment only (`advance_amount` + `transaction_flow=part`); never auto-clears the loan

### Legacy agency names on old PG links

Older agency PG links may have an empty or placeholder `agency_name` (e.g. `Agency`), or `created_by_id = 0` when the link was created via API before session context loaded. The admin PG tab and **Agency wise payments** CSV resolve agency from txnid (`PG_agency_admin_{id}_…`), `agency_id`, or `loan.paid_via_agency_*`.

One-time backfill (optional, persists names in DB):

```bash
sudo -u www-data php scripts/backfill_pg_link_agency.php --list
sudo -u www-data php scripts/backfill_pg_link_agency.php --apply
```

## Agency admin

- URL: `/agency_admin/`
- Login: same `/account/login.php` as other staff
- List: only **DPD &gt; 35** (same columns as account manager default bucket)
- Profile: Personal, Reference, Login Data, All Loan, Note, Account manager, **PG link**
- PAN / Aadhar hidden
