-- ============================================================
-- Shared Hosting Performance Indexes for Laravel/vBulletin Forum
-- ============================================================
-- Run manually in phpMyAdmin/MySQL during low traffic after taking a DB backup.
-- These statements add indexes only; they do not modify data.
-- If MySQL reports an index already exists, skip that statement.

-- Forum and thread listing hot paths
ALTER TABLE thread ADD INDEX idx_thread_visible_forum_sticky_dateline (visible, forumid, sticky, dateline);
ALTER TABLE thread ADD INDEX idx_thread_visible_dateline (visible, dateline);
ALTER TABLE thread ADD INDEX idx_thread_visible_views (visible, views);
ALTER TABLE thread ADD INDEX idx_thread_forum_threadid_visible (forumid, threadid, visible);

-- Thread pages and search excerpts
ALTER TABLE post ADD INDEX idx_post_thread_visible_dateline (threadid, visible, dateline);
ALTER TABLE post ADD INDEX idx_post_thread_visible_postid (threadid, visible, postid);

-- Online users/session tracking
ALTER TABLE session ADD INDEX idx_session_lastactivity (lastactivity);
ALTER TABLE session ADD INDEX idx_session_host_useragent_userid (host, useragent, userid);

-- Permissions / navigation checks
ALTER TABLE forumpermission ADD INDEX idx_forumpermission_forum_usergroup (forumid, usergroupid);
ALTER TABLE forum ADD INDEX idx_forum_parent_display (parentid, displayorder);

-- Optional but strongly recommended for search if not already applied.
-- ALTER TABLE thread ADD FULLTEXT INDEX ft_thread_title (title);
-- ALTER TABLE post ADD FULLTEXT INDEX ft_post_pagetext (pagetext);