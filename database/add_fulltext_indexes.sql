-- ============================================================
-- FULLTEXT Indexes for Orsozox Forum Search System
-- ============================================================
-- 
-- PREREQUISITES:
--   ✅ MySQL 5.6+ (InnoDB supports FULLTEXT since 5.6)
--   ✅ Backup your database BEFORE running these statements
--   ✅ Run during low-traffic period
--
-- SAFETY:
--   • These statements ADD indexes only — no columns are modified
--   • No existing data is changed
--   • vBulletin structure remains 100% intact
--   • Can be reversed with: DROP INDEX ft_thread_title ON thread;
--
-- ESTIMATED TIME:
--   • thread table (~70K rows): ~5-15 seconds
--   • post table (~600K rows): ~1-5 minutes
-- ============================================================

-- Step 1: Verify MySQL version supports FULLTEXT on InnoDB
SELECT VERSION() AS mysql_version;

-- Step 2: Check if indexes already exist (run these first)
-- SHOW INDEX FROM thread WHERE Key_name = 'ft_thread_title';
-- SHOW INDEX FROM post WHERE Key_name = 'ft_post_pagetext';

-- Step 3: Add FULLTEXT index on thread.title
ALTER TABLE thread ADD FULLTEXT INDEX ft_thread_title (title);

-- Step 4: Add FULLTEXT index on post.pagetext
ALTER TABLE post ADD FULLTEXT INDEX ft_post_pagetext (pagetext);

-- Step 5: Verify indexes were created successfully
SHOW INDEX FROM thread WHERE Key_name = 'ft_thread_title';
SHOW INDEX FROM post WHERE Key_name = 'ft_post_pagetext';

-- Step 6: Test FULLTEXT search works
-- SELECT threadid, title, MATCH(title) AGAINST('صلاة' IN BOOLEAN MODE) AS score 
-- FROM thread 
-- WHERE MATCH(title) AGAINST('صلاة' IN BOOLEAN MODE) 
-- LIMIT 5;
