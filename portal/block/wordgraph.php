<?php
/*
*
* @package phpBB3 Portal  a.k.a canverPortal  ( www.phpbb3portal.com )
* @version $Id: wordgraph.php,v 1.2 2007/08/19 17:51:00 angelside Exp $
* @copyright (c) Canver Software - www.canversoft.net
* @license http://opensource.org/licenses/gpl-license.php GNU Public License 
*
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

/**
*/
$words_array = array();

// Get words and number of those words
$sql = 'SELECT l.word_text, COUNT(*) AS word_count  
	FROM ' . SEARCH_WORDLIST_TABLE . ' AS l, ' . SEARCH_WORDMATCH_TABLE . ' AS m
	WHERE m.word_id = l.word_id 
	GROUP BY m.word_id 
	ORDER BY word_count DESC';
$result = $db->sql_query_limit($sql, $config['portal_wordgraph_max_words']);

while ($row = $db->sql_fetchrow($result))
{
	$word = strtolower($row['word_text']);
	$words_array[$word] = $row['word_count'];
}
$db->sql_freeresult($result);

$minimum = 1000000;
$maximum = -1000000;

foreach ( array_keys($words_array) as $word )
{
	if ( $words_array[$word] > $maximum )
	{
		$maximum = $words_array[$word];
	}
	
	if ( $words_array[$word] < $minimum )
	{
		$minimum = $words_array[$word];
	}
}

// ratio
$ratio = $config['portal_wordgraph_ratio'] / ( $maximum - $minimum);

$words = array_keys($words_array);
sort($words);

foreach ( $words as $word )
{
	$template->assign_block_vars('wordgraph', array(
		'WORD' 				=> ($config['portal_wordgraph_word_counts']) ? $word . '(' . $words_array[$word] . ')' : $word,
		'WORD_FONT_SIZE' 	=> (int) ( 9 + ( $words_array[$word] * $ratio ) ),
		'WORD_SEARCH_URL' 	=> append_sid("{$phpbb_root_path}search.$phpEx", 'keywords=' . urlencode($word)),
	));
}

$template->assign_vars(array(
	'S_DISPLAY_WORDGRAPH' => true,
	'L_WORDGRAPH' => $user->lang['WORDGRAPH'],
	)
);

?>