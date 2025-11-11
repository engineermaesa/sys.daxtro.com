-- ============================================================
-- DATA TEST UNTUK AUTO-TRASH FUNCTIONALITY
-- File: test_data_auto_trash.sql
-- ============================================================

-- CATATAN PENTING:
-- Ganti USER_ID_SALES dengan ID user sales yang ada di sistem Anda
-- Pastikan lead_sources, lead_segments, dan ref_regions sudah ada data

SET @USER_ID_SALES = 1; -- GANTI DENGAN ID USER SALES YANG ADA

-- ============================================================
-- 1. INSERT TEST DATA COLD LEADS (lebih dari 10 hari)
-- ============================================================

-- Cold Lead Test 1 - 15 hari yang lalu
INSERT INTO leads (source_id, segment_id, region_id, status_id, name, phone, email, needs, published_at, created_at, updated_at) 
VALUES (1, 1, 1, 2, 'Test Cold Lead 1 - 15 Days Old', '081234567890', 'cold1@test.com', 'Testing auto-trash cold leads', DATE_SUB(NOW(), INTERVAL 15 DAY), DATE_SUB(NOW(), INTERVAL 15 DAY), DATE_SUB(NOW(), INTERVAL 15 DAY));

SET @cold_lead_1_id = LAST_INSERT_ID();

-- Claim untuk cold lead 1 (15 hari yang lalu)
INSERT INTO lead_claims (lead_id, sales_id, claimed_at, created_at, updated_at) 
VALUES (@cold_lead_1_id, @USER_ID_SALES, DATE_SUB(NOW(), INTERVAL 15 DAY), DATE_SUB(NOW(), INTERVAL 15 DAY), DATE_SUB(NOW(), INTERVAL 15 DAY));

-- Status log untuk cold lead 1
INSERT INTO lead_status_logs (lead_id, status_id, created_at, updated_at) 
VALUES (@cold_lead_1_id, 2, DATE_SUB(NOW(), INTERVAL 15 DAY), DATE_SUB(NOW(), INTERVAL 15 DAY));

-- Cold Lead Test 2 - 12 hari yang lalu
INSERT INTO leads (source_id, segment_id, region_id, status_id, name, phone, email, needs, published_at, created_at, updated_at) 
VALUES (1, 2, 1, 2, 'Test Cold Lead 2 - 12 Days Old', '081234567891', 'cold2@test.com', 'Testing auto-trash cold leads', DATE_SUB(NOW(), INTERVAL 12 DAY), DATE_SUB(NOW(), INTERVAL 12 DAY), DATE_SUB(NOW(), INTERVAL 12 DAY));

SET @cold_lead_2_id = LAST_INSERT_ID();

-- Claim untuk cold lead 2 (12 hari yang lalu)
INSERT INTO lead_claims (lead_id, sales_id, claimed_at, created_at, updated_at) 
VALUES (@cold_lead_2_id, @USER_ID_SALES, DATE_SUB(NOW(), INTERVAL 12 DAY), DATE_SUB(NOW(), INTERVAL 12 DAY), DATE_SUB(NOW(), INTERVAL 12 DAY));

-- Status log untuk cold lead 2
INSERT INTO lead_status_logs (lead_id, status_id, created_at, updated_at) 
VALUES (@cold_lead_2_id, 2, DATE_SUB(NOW(), INTERVAL 12 DAY), DATE_SUB(NOW(), INTERVAL 12 DAY));

-- Cold Lead Test 3 - 20 hari yang lalu
INSERT INTO leads (source_id, segment_id, region_id, status_id, name, phone, email, needs, published_at, created_at, updated_at) 
VALUES (1, 1, 2, 2, 'Test Cold Lead 3 - 20 Days Old', '081234567892', 'cold3@test.com', 'Testing auto-trash cold leads', DATE_SUB(NOW(), INTERVAL 20 DAY), DATE_SUB(NOW(), INTERVAL 20 DAY), DATE_SUB(NOW(), INTERVAL 20 DAY));

SET @cold_lead_3_id = LAST_INSERT_ID();

-- Claim untuk cold lead 3 (20 hari yang lalu)
INSERT INTO lead_claims (lead_id, sales_id, claimed_at, created_at, updated_at) 
VALUES (@cold_lead_3_id, @USER_ID_SALES, DATE_SUB(NOW(), INTERVAL 20 DAY), DATE_SUB(NOW(), INTERVAL 20 DAY), DATE_SUB(NOW(), INTERVAL 20 DAY));

-- Status log untuk cold lead 3
INSERT INTO lead_status_logs (lead_id, status_id, created_at, updated_at) 
VALUES (@cold_lead_3_id, 2, DATE_SUB(NOW(), INTERVAL 20 DAY), DATE_SUB(NOW(), INTERVAL 20 DAY));

-- ============================================================
-- 2. INSERT TEST DATA WARM LEADS (lebih dari 30 hari)
-- ============================================================

-- Warm Lead Test 1 - 35 hari yang lalu
INSERT INTO leads (source_id, segment_id, region_id, status_id, name, phone, email, needs, published_at, created_at, updated_at) 
VALUES (2, 1, 1, 3, 'Test Warm Lead 1 - 35 Days Old', '081234567893', 'warm1@test.com', 'Testing auto-trash warm leads', DATE_SUB(NOW(), INTERVAL 35 DAY), DATE_SUB(NOW(), INTERVAL 35 DAY), DATE_SUB(NOW(), INTERVAL 35 DAY));

SET @warm_lead_1_id = LAST_INSERT_ID();

-- Claim untuk warm lead 1 (35 hari yang lalu)
INSERT INTO lead_claims (lead_id, sales_id, claimed_at, created_at, updated_at) 
VALUES (@warm_lead_1_id, @USER_ID_SALES, DATE_SUB(NOW(), INTERVAL 35 DAY), DATE_SUB(NOW(), INTERVAL 35 DAY), DATE_SUB(NOW(), INTERVAL 35 DAY));

-- Status log untuk warm lead 1
INSERT INTO lead_status_logs (lead_id, status_id, created_at, updated_at) 
VALUES (@warm_lead_1_id, 3, DATE_SUB(NOW(), INTERVAL 35 DAY), DATE_SUB(NOW(), INTERVAL 35 DAY));

-- Warm Lead Test 2 - 40 hari yang lalu
INSERT INTO leads (source_id, segment_id, region_id, status_id, name, phone, email, needs, published_at, created_at, updated_at) 
VALUES (2, 2, 2, 3, 'Test Warm Lead 2 - 40 Days Old', '081234567894', 'warm2@test.com', 'Testing auto-trash warm leads', DATE_SUB(NOW(), INTERVAL 40 DAY), DATE_SUB(NOW(), INTERVAL 40 DAY), DATE_SUB(NOW(), INTERVAL 40 DAY));

SET @warm_lead_2_id = LAST_INSERT_ID();

-- Claim untuk warm lead 2 (40 hari yang lalu)
INSERT INTO lead_claims (lead_id, sales_id, claimed_at, created_at, updated_at) 
VALUES (@warm_lead_2_id, @USER_ID_SALES, DATE_SUB(NOW(), INTERVAL 40 DAY), DATE_SUB(NOW(), INTERVAL 40 DAY), DATE_SUB(NOW(), INTERVAL 40 DAY));

-- Status log untuk warm lead 2
INSERT INTO lead_status_logs (lead_id, status_id, created_at, updated_at) 
VALUES (@warm_lead_2_id, 3, DATE_SUB(NOW(), INTERVAL 40 DAY), DATE_SUB(NOW(), INTERVAL 40 DAY));

-- Warm Lead Test 3 - 50 hari yang lalu
INSERT INTO leads (source_id, segment_id, region_id, status_id, name, phone, email, needs, published_at, created_at, updated_at) 
VALUES (1, 2, 1, 3, 'Test Warm Lead 3 - 50 Days Old', '081234567895', 'warm3@test.com', 'Testing auto-trash warm leads', DATE_SUB(NOW(), INTERVAL 50 DAY), DATE_SUB(NOW(), INTERVAL 50 DAY), DATE_SUB(NOW(), INTERVAL 50 DAY));

SET @warm_lead_3_id = LAST_INSERT_ID();

-- Claim untuk warm lead 3 (50 hari yang lalu)
INSERT INTO lead_claims (lead_id, sales_id, claimed_at, created_at, updated_at) 
VALUES (@warm_lead_3_id, @USER_ID_SALES, DATE_SUB(NOW(), INTERVAL 50 DAY), DATE_SUB(NOW(), INTERVAL 50 DAY), DATE_SUB(NOW(), INTERVAL 50 DAY));

-- Status log untuk warm lead 3
INSERT INTO lead_status_logs (lead_id, status_id, created_at, updated_at) 
VALUES (@warm_lead_3_id, 3, DATE_SUB(NOW(), INTERVAL 50 DAY), DATE_SUB(NOW(), INTERVAL 50 DAY));

-- ============================================================
-- 3. INSERT TEST DATA NORMAL (dalam batas waktu) - CONTROL GROUP
-- ============================================================

-- Cold Lead Normal - 5 hari yang lalu (masih dalam batas)
INSERT INTO leads (source_id, segment_id, region_id, status_id, name, phone, email, needs, published_at, created_at, updated_at) 
VALUES (1, 1, 1, 2, 'Test Cold Lead Normal - 5 Days Old', '081234567896', 'coldnormal@test.com', 'Control group - should NOT be trashed', DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY));

SET @cold_normal_id = LAST_INSERT_ID();

-- Claim untuk cold normal (5 hari yang lalu)
INSERT INTO lead_claims (lead_id, sales_id, claimed_at, created_at, updated_at) 
VALUES (@cold_normal_id, @USER_ID_SALES, DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY));

-- Status log untuk cold normal
INSERT INTO lead_status_logs (lead_id, status_id, created_at, updated_at) 
VALUES (@cold_normal_id, 2, DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY));

-- Warm Lead Normal - 20 hari yang lalu (masih dalam batas)
INSERT INTO leads (source_id, segment_id, region_id, status_id, name, phone, email, needs, published_at, created_at, updated_at) 
VALUES (2, 1, 1, 3, 'Test Warm Lead Normal - 20 Days Old', '081234567897', 'warmnormal@test.com', 'Control group - should NOT be trashed', DATE_SUB(NOW(), INTERVAL 20 DAY), DATE_SUB(NOW(), INTERVAL 20 DAY), DATE_SUB(NOW(), INTERVAL 20 DAY));

SET @warm_normal_id = LAST_INSERT_ID();

-- Claim untuk warm normal (20 hari yang lalu)
INSERT INTO lead_claims (lead_id, sales_id, claimed_at, created_at, updated_at) 
VALUES (@warm_normal_id, @USER_ID_SALES, DATE_SUB(NOW(), INTERVAL 20 DAY), DATE_SUB(NOW(), INTERVAL 20 DAY), DATE_SUB(NOW(), INTERVAL 20 DAY));

-- Status log untuk warm normal
INSERT INTO lead_status_logs (lead_id, status_id, created_at, updated_at) 
VALUES (@warm_normal_id, 3, DATE_SUB(NOW(), INTERVAL 20 DAY), DATE_SUB(NOW(), INTERVAL 20 DAY));

-- ============================================================
-- 4. QUERY UNTUK VERIFIKASI DATA TEST
-- ============================================================

-- Cek data yang baru diinsert
SELECT 'COLD LEADS (should be auto-trashed)' as category, 
       l.id, l.name, l.status_id, 
       lc.claimed_at,
       DATEDIFF(NOW(), lc.claimed_at) as days_old,
       CASE WHEN DATEDIFF(NOW(), lc.claimed_at) > 10 THEN 'SHOULD BE TRASHED' ELSE 'OK' END as trash_status
FROM leads l 
JOIN lead_claims lc ON l.id = lc.lead_id 
WHERE l.name LIKE 'Test Cold Lead%' AND l.status_id = 2
AND lc.released_at IS NULL

UNION ALL

SELECT 'WARM LEADS (should be auto-trashed)' as category,
       l.id, l.name, l.status_id,
       lc.claimed_at,
       DATEDIFF(NOW(), lc.claimed_at) as days_old,
       CASE WHEN DATEDIFF(NOW(), lc.claimed_at) > 30 THEN 'SHOULD BE TRASHED' ELSE 'OK' END as trash_status
FROM leads l 
JOIN lead_claims lc ON l.id = lc.lead_id 
WHERE l.name LIKE 'Test Warm Lead%' AND l.status_id = 3
AND lc.released_at IS NULL

ORDER BY category, days_old DESC;

-- ============================================================
-- 5. QUERY CLEANUP (jika diperlukan)
-- ============================================================

-- UNCOMMENT LINES BELOW UNTUK MENGHAPUS DATA TEST
/*
DELETE lsl FROM lead_status_logs lsl 
JOIN leads l ON lsl.lead_id = l.id 
WHERE l.name LIKE 'Test %Lead%';

DELETE lc FROM lead_claims lc 
JOIN leads l ON lc.lead_id = l.id 
WHERE l.name LIKE 'Test %Lead%';

DELETE FROM leads WHERE name LIKE 'Test %Lead%';
*/