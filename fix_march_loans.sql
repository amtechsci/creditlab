-- ============================================
-- PART 1: Find PENDING DISBURSAL loans (loan_apply) that need fixing
-- ============================================

-- 1A: Count pending disbursal loans by status
SELECT 
    la.status,
    COUNT(*) as count
FROM loan_apply la
INNER JOIN user u ON la.uid = u.id
WHERE la.status IN ('disbursal', 'pending', 'follow up')
AND (u.approvenew = 0 OR u.approvenew IS NULL)
AND u.salary_date IS NOT NULL 
AND u.salary_date != '' 
AND u.salary_date != '0'
AND u.salary_date REGEXP '^[0-9]+$'
GROUP BY la.status;

-- 1B: List all pending disbursal loans with salary_date (to see their current days)
SELECT 
    la.id as loan_apply_id,
    la.uid,
    la.status,
    u.name,
    u.salary_date,
    DATE(la.apply_date) as apply_date,
    la.days as current_days,
    DATE_ADD(DATE(la.apply_date), INTERVAL la.days DAY) as current_due_date
FROM loan_apply la
INNER JOIN user u ON la.uid = u.id
WHERE la.status IN ('disbursal', 'pending', 'follow up')
AND (u.approvenew = 0 OR u.approvenew IS NULL)
AND u.salary_date IS NOT NULL 
AND u.salary_date != '' 
AND u.salary_date != '0'
AND u.salary_date REGEXP '^[0-9]+$'
AND CAST(u.salary_date AS UNSIGNED) BETWEEN 1 AND 31
ORDER BY la.status, la.id DESC;

-- 1C: Find pending disbursal with days=30 that likely need recalculation
SELECT 
    la.id as loan_apply_id,
    la.uid,
    la.status,
    u.name,
    u.salary_date,
    DATE(la.apply_date) as apply_date,
    la.days as current_days,
    DATE_ADD(DATE(la.apply_date), INTERVAL la.days DAY) as current_due_date
FROM loan_apply la
INNER JOIN user u ON la.uid = u.id
WHERE la.status IN ('disbursal', 'pending', 'follow up')
AND la.days = 30
AND (u.approvenew = 0 OR u.approvenew IS NULL)
AND u.salary_date IS NOT NULL 
AND u.salary_date != '' 
AND u.salary_date != '0'
AND u.salary_date REGEXP '^[0-9]+$'
AND CAST(u.salary_date AS UNSIGNED) BETWEEN 1 AND 31
ORDER BY la.status, la.id DESC;

-- ============================================
-- PART 2: Find DISBURSED loans (loan table) on "account manager" with March due dates
-- ============================================

-- 2A: Count disbursed loans by due date in March
SELECT 
    DATE_ADD(DATE(l.processed_date), INTERVAL l.total_time DAY) as due_date,
    COUNT(*) as loan_count
FROM loan l
WHERE l.action = 'account manager'
AND (l.is_emi = 0 OR l.is_emi IS NULL)
AND DATE_ADD(DATE(l.processed_date), INTERVAL l.total_time DAY) BETWEEN '2026-03-01' AND '2026-03-31'
GROUP BY DATE_ADD(DATE(l.processed_date), INTERVAL l.total_time DAY)
ORDER BY due_date;

-- 2B: List disbursed loans with March due dates
SELECT 
    l.id as loan_id,
    l.lid as loan_apply_id,
    l.uid,
    u.name,
    u.salary_date,
    DATE(l.processed_date) as processed_date,
    l.total_time as current_days,
    DATE_ADD(DATE(l.processed_date), INTERVAL l.total_time DAY) as current_due_date
FROM loan l
INNER JOIN user u ON l.uid = u.id
WHERE l.action = 'account manager'
AND (l.is_emi = 0 OR l.is_emi IS NULL)
AND DATE_ADD(DATE(l.processed_date), INTERVAL l.total_time DAY) BETWEEN '2026-03-01' AND '2026-03-31'
ORDER BY DATE_ADD(DATE(l.processed_date), INTERVAL l.total_time DAY), l.id;

-- 2C: Specifically loans due March 28-31 (salary_date 28-31 that should be Feb 28)
SELECT 
    l.id as loan_id,
    l.lid as loan_apply_id,
    l.uid,
    u.name,
    u.salary_date,
    DATE(l.processed_date) as processed_date,
    l.total_time as current_days,
    DATE_ADD(DATE(l.processed_date), INTERVAL l.total_time DAY) as current_due_date
FROM loan l
INNER JOIN user u ON l.uid = u.id
WHERE l.action = 'account manager'
AND (l.is_emi = 0 OR l.is_emi IS NULL)
AND u.salary_date IN (28, 29, 30, 31)
AND DATE_ADD(DATE(l.processed_date), INTERVAL l.total_time DAY) IN ('2026-03-28', '2026-03-29', '2026-03-30', '2026-03-31')
ORDER BY l.id;
