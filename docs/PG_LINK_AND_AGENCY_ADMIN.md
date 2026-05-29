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

- **Total outstanding** (or manual ≥ outstanding) → loan cleared, agency name stored on loan
- **Manual &lt; outstanding** → part payment (`advance_amount` + `transaction_flow=part`)

## Agency admin

- URL: `/agency_admin/`
- Login: same `/account/login.php` as other staff
- List: only **DPD &gt; 35** (same columns as account manager default bucket)
- Profile: Personal, Reference, Login Data, All Loan, Note, Account manager, **PG link**
- PAN / Aadhar hidden
