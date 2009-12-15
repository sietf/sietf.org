<?php
/*
*
* @package phpBB3 Portal  a.k.a canverPortal  ( www.phpbb3portal.com )
* @version $Id: portal.php,v 1.5 2007/08/19 17:51:00 angelside Exp $
* @copyright (c) Canver Software - www.canversoft.net
* @license http://opensource.org/licenses/gpl-license.php GNU Public License 
*
*/

// Note: If you would like to have the portal in a different location than in the main phpBB3 directory
// You must change the following variable, and change the 'U_PORTAL' template variable in functions.php
define('IN_PHPBB', true);
define('IN_PORTAL', true);

$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './';
$phpbb_portal_path = (defined('PHPBB_PORTAL_PATH')) ? PHPBB_PORTAL_PATH : './portal/';

$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);
include($phpbb_portal_path . '/includes/functions.'.$phpEx);

// Start session management
$user->session_begin();
$auth->acl($user->data);
$user->setup('portal');

// show login box and user menu

//  acp de aç/kapa yok, dil dosyasýna ek: hiç doðumgünü yoksa evet seçili olsa bile blok görünmez.
// SQL bilgisi yok -  - SQL eklenirse dil deðiþkeni de eklenmeli
//if ($config['portal_user_menu'])
//{
	// only registered user see user menu
	if ($user->data['is_registered'])
	{
		include($phpbb_portal_path . '/block/user_menu.'.$phpEx);
	}
	else
	{
		include($phpbb_portal_path . '/block/login_box.'.$phpEx);
	}
//}

if ($config['portal_attachments'])
{
	include($phpbb_portal_path . '/block/attachments.'.$phpEx);
}

if ($config['portal_recent']) 
{ 
	include($phpbb_portal_path . '/block/recent.'.$phpEx);
}

if ($config['portal_advanced_stat'])
{
	include($phpbb_portal_path . '/block/statistics.'.$phpEx);
}

if ($config['portal_minicalendar'])
{
	include($phpbb_portal_path . '/block/mini_cal.'.$phpEx);
}

if ($config['portal_link_us'])
{
	include($phpbb_portal_path . '/block/link_us.'.$phpEx);
}

if ($config['portal_leaders'])
{
	include($phpbb_portal_path . '/block/leaders.'.$phpEx);
}

if ($config['portal_wordgraph'])
{
	include($phpbb_portal_path . '/block/wordgraph.'.$phpEx);
}

if ($config['portal_poll_topic'])
{
	include($phpbb_portal_path . '/block/poll.'.$phpEx);
}

if ($config['portal_load_last_visited_bots'])
{
	include($phpbb_portal_path . '/block/latest_bots.'.$phpEx);
}

if ($config['portal_top_posters'])
{
	include($phpbb_portal_path . '/block/top_posters.'.$phpEx);
}

if ($config['portal_latest_members'])
{
	include($phpbb_portal_path . '/block/latest_members.'.$phpEx);
}

// mod trooper
// DIE DIE RANDOM MEMBER BLOCK!!2
/*
if ($config['portal_random_member'])
{
	include($phpbb_portal_path . '/block/random_member.'.$phpEx);
}
*/

if ($config['portal_clock'])
{
	$template->assign_vars(array(
		'S_DISPLAY_CLOCK' => true,
	));
}

if ($config['portal_links'])
{
//	include($phpbb_portal_path . '/block/links.'.$phpEx);
	$template->assign_vars(array(
		'S_DISPLAY_LINKS' => true,
	));
}

if ($config['portal_welcome'])
{
	$template->assign_vars(array(
		'S_DISPLAY_WELCOME' 	=> true,
		'PORTAL_WELCOME_INTRO'	=> $config['portal_welcome_intro'],
	));
}

if ($config['portal_announcements'])
{
	include($phpbb_portal_path . '/block/announcements.'.$phpEx);
	$template->assign_vars(array(
		'S_ANNOUNCE_COMPACT' => ($config['portal_announcements_style']) ? true : false,
	));
}

if ($config['portal_news'])
{
	include($phpbb_portal_path . '/block/news.'.$phpEx);
	$template->assign_vars(array(
		'S_NEWS_COMPACT' => ($config['portal_news_style']) ? true : false,
	));
}

if ($config['portal_pay_s_block'] or $config['portal_pay_c_block'])
{
	include($phpbb_portal_path . '/block/donate.'.$phpEx);
}

/*
if ($config['portal_ads_small'])
{
	$template->assign_vars(array(
		'S_ADS_SMALL' 	=> ($config['portal_ads_small_box']) ? true : false,
	//	'ADS_SMALL_BOX'	=> $config['portal_ads_small_box'],
	));
}

if ($config['portal_ads_center'])
{
	$template->assign_vars(array(
		'S_ADS_CENTER' 		=> ($config['portal_ads_center_box']) ? true : false,
	//	'ADS_CENTER_BOX'	=> $config['portal_ads_center_box'],
	));
}
*/

// acp de aç/kapa yok - SQL bilgisi yok - SQL eklenirse dil deðiþkeni de eklenmeli
if ($user->data['is_registered']/* and $config['portal_friends']*/)
{
	include($phpbb_portal_path . '/block/friends.'.$phpEx);
}

// acp de aç/kapa yok - SQL bilgisi yok - SQL eklenirse dil deðiþkeni de eklenmeli
// dil dosyasýna ek: hiç doðumgünü yoksa evet seçili olsa bile blok görünmez.
//if ($config['show_birthdays'])
//{
	include($phpbb_portal_path . '/block/birthday_list.'.$phpEx);
//}

// acp de aç/kapa yok - SQL bilgisi yok - SQL eklenirse dil deðiþkeni de eklenmeli
//if ($config['show_whois_online'])
//{
	include($phpbb_portal_path . '/block/whois_online.'.$phpEx);
//}

// acp de aç/kapa yok - SQL bilgisi yok - SQL eklenirse dil deðiþkeni de eklenmeli
//if ($config['show_search'])
//{
	include($phpbb_portal_path . '/block/search.'.$phpEx);
//}

// acp de aç/kapa yok - SQL bilgisi yok - SQL eklenirse dil deðiþkeni de eklenmeli
//if ($config['change_style'])
//{
//	include($phpbb_portal_path . '/block/change_style.'.$phpEx); // stil seçince hata veriyor
//}

$template->assign_vars(array(
	'S_DISPLAY_JUMPBOX' 	=> true, // SQL + ACP eklenecek
	'PORTAL_LEFT_COLLUMN' 	=> $config['portal_left_collumn_width'],
	'PORTAL_RIGHT_COLLUMN' 	=> $config['portal_right_collumn_width'],
));

// output page
//page_header($user->lang['PORTAL']);
page_header($config['sitename']);

$template->set_filenames(array(
	'body' => $phpbb_portal_path . '/portal_body.html'
));

// SQL + ACP eklenecek
make_jumpbox(append_sid("{$phpbb_root_path}viewforum.$phpEx"));

page_footer();

?>