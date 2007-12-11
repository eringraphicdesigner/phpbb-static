<?

require_once('config.php');
require_once('common.php');

function generate_topics() {
	global $topics, $forums;

	log_info("Wrote (topics):");

	while (list($tid, $topic) = each($topics)) {
		
		$fid = $topics[$tid]['fid'];
		$var = array();
		$var['forum_title'] = $forums[$fid]['title'];
		$var['title'] = $topics[$tid]['title'];
		$var['tid'] = $tid;
		$var['posts'] = array();

		$res = mysql_query('SELECT p.post_id, p.poster_id, p.post_username, u.username, p.post_time, pt.post_subject, pt.post_text, pt.bbcode_uid FROM phpbb_posts p LEFT JOIN phpbb_users u ON p.poster_id=u.user_id LEFT JOIN phpbb_posts_text pt ON p.post_id=pt.post_id WHERE p.topic_id=' . $tid . ' ORDER BY p.post_time ASC');

		while ($row = mysql_fetch_assoc($res)) {
			$var['posts'][] = array(
				'username'   => $row['username'],
				'post_text'  => $row['post_text'],
				'post_time'  => $row['post_time'],
				'bbcde_uid'  => $row['bbcode_uid']
			);
		}
		

		$content = template_get($var, 'topic.tpl.php');
		write_content($fid . '/t-' . $tid . '.html', $content);

		log_info(" $tid");
	}

	log_info("\n");
}

function generate_forums() {
	global $forums, $topics;
	global $filter_forum, $filter_topic;

	$res = mysql_query('SELECT t.forum_id, t.topic_id, t.topic_title, t.topic_time, t.topic_replies, u.username FROM phpbb_topics t LEFT JOIN phpbb_users u ON t.topic_poster=u.user_id WHERE t.topic_moved_id = 0 ORDER BY t.topic_time DESC');

	while ($row = mysql_fetch_assoc($res)) {
		$fid = $row['forum_id'];

		if (in_array($fid, $filter_forum)) {
			continue;
		}

		$topics[$row['topic_id']] = array(
			'fid'     => $fid,
			'title'   => $row['topic_title'],
			'time'    => $row['topic_time'],
			'replies' => $row['topic_replies'],
			'author'  => $row['username']
		);
		$forums[$fid]['topics'][] = $row['topic_id'];
	}

	log_info("Wrote (forum index):");
	while (list($fid, $forum) = each($forums)) {
		$var = array(
			'topics' => $topics,
			'list'   => $forums[$fid]['topics'],
		);

		$content = template_get($var, 'forum.tpl.php');
		write_content($fid . '/index.html', $content);

		log_info(" $fid");
	}
	log_info("\n");

}

function generate_main() {
	global $categories, $forums;
	global $filter_forum, $filter_topic;

	//Categories
	$res = mysql_query('SELECT cat_id, cat_title FROM phpbb_categories order by cat_order');

	while ($row = mysql_fetch_assoc($res)) {
		$cid = $row['cat_id'];
		$categories[$row['cat_id']] = array(
			'title'  => $row['cat_title'],
			'forums' => array()
		);
	}

	//Forums
	$res = mysql_query('SELECT forum_id, cat_id, forum_name, forum_posts, forum_topics FROM phpbb_forums ORDER BY forum_order');

	while ($row = mysql_fetch_assoc($res)) {
		$fid = $row['forum_id'];

		if (in_array($fid, $filter_forum)) {
			continue;
		}

		$forums[$fid] = array(
			'cid'     => $row['cat_id'],
			'title'   => $row['forum_name'],
			'nposts'  => $row['forum_posts'],
			'ntopics' => $row['forum_topics'],
			'topics'  => array()
		);
		$categories[$row['cat_id']]['forums'][] = $fid;
	}

	// Content
	$var = array(
		'categories' => $categories,
		'forums'     => $forums
	);
	$content = template_get($var, 'main.tpl.php');

	write_content('index.html', $content);

	log_info("Wrote: index.html\n");

}

mysql_connect($db_host, $db_user, $db_pass);
mysql_select_db($db_name);

$categories = array();
$forums = array();
$topics = array();

generate_main();
generate_forums();
generate_topics();

