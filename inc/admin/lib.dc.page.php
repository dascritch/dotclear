<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) 2003-2011 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------
if (!defined('DC_RC_PATH')) { return; }

define('DC_AUTH_PAGE','auth.php');

class dcPage
{
	private static $loaded_js=array();

	# Auth check
	public static function check($permissions)
	{
		global $core;
		
		if ($core->blog && $core->auth->check($permissions,$core->blog->id))
		{
			return;
		}
		
		if (session_id()) {
			$core->session->destroy();
		}
		http::redirect(DC_AUTH_PAGE);
	}
	
	# Check super admin
	public static function checkSuper()
	{
		global $core;
		
		if (!$core->auth->isSuperAdmin())
		{
			if (session_id()) {
				$core->session->destroy();
			}
			http::redirect(DC_AUTH_PAGE);
		}
	}
	
	# Top of admin page
	public static function open($title='', $head='')
	{
		global $core;
		
		# List of user's blogs
		if ($core->auth->blog_count == 1 || $core->auth->blog_count > 20)
		{
			$blog_box =
			'<p>'.__('Blog:').' <strong title="'.html::escapeHTML($core->blog->url).'">'.
			html::escapeHTML($core->blog->name).'</strong>';
			
			if ($core->auth->blog_count > 20) {
				$blog_box .= ' - <a href="blogs.php">'.__('Change blog').'</a>';
			}
			$blog_box .= '</p>';
		}
		else
		{
			$rs_blogs = $core->getBlogs(array('order'=>'LOWER(blog_name)','limit'=>20));
			$blogs = array();
			foreach ($rs_blogs as $rs_blog) {
				$blogs[html::escapeHTML($rs_blog->blog_name.' - '.$rs_blog->blog_url)] = $rs_blog->blog_id;
			}
			$blog_box =
			'<p><label for="switchblog" class="classic">'.
			__('Blogs:').' '.
			$core->formNonce().
			form::combo('switchblog',$blogs,$core->blog->id).
			'</label></p>'.
			'<noscript><p><input type="submit" value="'.__('ok').'" /></p></noscript>';
		}
		
		$safe_mode = isset($_SESSION['sess_safe_mode']) && $_SESSION['sess_safe_mode'];
		
		# Display
		header('Content-Type: text/html; charset=UTF-8');
		echo
		'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" '.
		' "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">'."\n".
		'<html xmlns="http://www.w3.org/1999/xhtml" '.
		'xml:lang="'.$core->auth->getInfo('user_lang').'" '.
		'lang="'.$core->auth->getInfo('user_lang').'">'."\n".
		"<head>\n".
		'  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />'."\n".
		'  <title>'.$title.' - '.html::escapeHTML($core->blog->name).' - '.html::escapeHTML(DC_VENDOR_NAME).' - '.DC_VERSION.'</title>'."\n".
		
		'  <meta name="ROBOTS" content="NOARCHIVE,NOINDEX,NOFOLLOW" />'."\n".
		'  <meta name="GOOGLEBOT" content="NOSNIPPET" />'."\n".
		
		self::jsLoadIE7().
		'  	<link rel="stylesheet" href="style/default.css" type="text/css" media="screen" />'."\n"; 
		if (l10n::getTextDirection($GLOBALS['_lang']) == 'rtl') {
			echo
		'  	<link rel="stylesheet" href="style/default-rtl.css" type="text/css" media="screen" />'."\n"; 
		}

		$core->auth->user_prefs->addWorkspace('interface');
		$user_ui_hide_std_favicon = $core->auth->user_prefs->interface->hide_std_favicon;
		if (!$user_ui_hide_std_favicon) {
			echo '<link rel="icon" type="image/png" href="images/favicon.png" />';
		}
		
		echo
		self::jsCommon().
		$head;
		
		# --BEHAVIOR-- adminPageHTMLHead
		$core->callBehavior('adminPageHTMLHead');
		
		echo
		"</head>\n".
		'<body id="dotclear-admin'.
		($safe_mode ? ' safe-mode' : '').
		'">'."\n".
		
		'<div id="header">'.
		'<ul id="prelude"><li><a href="#content">'.__('Go to the content').'</a></li><li><a href="#main-menu">'.__('Go to the menu').'</a></li></ul>'."\n".
		'<div id="top"><h1><a href="index.php">'.DC_VENDOR_NAME.'</a></h1></div>'."\n";	
		
		echo
		'<div id="info-boxes">'.
		'<div id="info-box1">'.
		'<form action="index.php" method="post">'.
		$blog_box.
		'<p><a href="'.$core->blog->url.'" onclick="window.open(this.href);return false;" title="'.__('Go to site').' ('.__('new window').')'.'">'.__('Go to site').' <img src="images/outgoing.png" alt="" /></a>'.
		'</p></form>'.
		'</div>'.
		'<div id="info-box2">'.
		'<a'.(preg_match('/index.php$/',$_SERVER['REQUEST_URI']) ? ' class="active"' : '').' href="index.php">'.__('My dashboard').'</a>'.
		'<span> | </span><a'.(preg_match('/preferences.php(\?.*)?$/',$_SERVER['REQUEST_URI']) ? ' class="active"' : '').' href="preferences.php">'.__('My preferences').'</a>'.
		'<span> | </span><a href="index.php?logout=1" class="logout">'.sprintf(__('Logout %s'),$core->auth->userID()).' <img src="images/logout.png" alt="" /></a>'.
		'</div>'.
		'</div>'.
		'</div>';
		
		echo
		'<div id="wrapper">'."\n".
		'<div id="main">'."\n".
		'<div id="content">'."\n";
		
		# Safe mode
		if ($safe_mode)
		{
			echo
			'<div class="error"><h3>'.__('Safe mode').'</h3>'.
			'<p>'.__('You are in safe mode. All plugins have been temporarily disabled. Remind to log out then log in again normally to get back all functionalities').'</p>'.
			'</div>';
		}
		
		if ($core->error->flag()) {
			echo
			'<div class="error"><p><strong>'.(count($core->error->getErrors()) > 1 ? __('Errors:') : __('Error:')).'</p></strong>'.
			$core->error->toHTML().
			'</div>';
		}
	}
	
	public static function close()
	{
		global $core;

		$menu =& $GLOBALS['_menu'];
		
		echo
		"</div>\n".		// End of #content
		"</div>\n".		// End of #main
		
		'<div id="main-menu">'."\n";
		
		foreach ($menu as $k => $v) {
			echo $menu[$k]->draw();
		}
		
		$text = sprintf(__('Thank you for using %s.'),'<a href="http://dotclear.org/">Dotclear '.DC_VERSION.'</a>');

		# --BEHAVIOR-- adminPageFooter
		$textAlt = $core->callBehavior('adminPageFooter',$core,$text);

		echo
		'</div>'."\n".		// End of #main-menu
		'<div id="footer"><p>'.($textAlt != '' ? $textAlt : $text).'</p></div>'."\n".
		"</div>\n";		// End of #wrapper
		
		if (defined('DC_DEV') && DC_DEV === true) {
			echo self::debugInfo();
		}
		
		echo
		'</body></html>';
	}
	
	public static function openPopup($title='', $head='')
	{
		global $core;
		
		# Display
		header('Content-Type: text/html; charset=UTF-8');
		echo
		'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" '.
		' "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">'."\n".
		'<html xmlns="http://www.w3.org/1999/xhtml" '.
		'xml:lang="'.$core->auth->getInfo('user_lang').'" '.
		'lang="'.$core->auth->getInfo('user_lang').'">'."\n".
		"<head>\n".
		'  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />'."\n".
		'  <title>'.$title.' - '.html::escapeHTML($core->blog->name).' - '.html::escapeHTML(DC_VENDOR_NAME).' - '.DC_VERSION.'</title>'."\n".
		
		'  <meta name="ROBOTS" content="NOARCHIVE,NOINDEX,NOFOLLOW" />'."\n".
		'  <meta name="GOOGLEBOT" content="NOSNIPPET" />'."\n".
		
		self::jsLoadIE7().
		'  	<link rel="stylesheet" href="style/default.css" type="text/css" media="screen" />'."\n"; 
		if (l10n::getTextDirection($GLOBALS['_lang']) == 'rtl') {
			echo
			'  	<link rel="stylesheet" href="style/default-rtl.css" type="text/css" media="screen" />'."\n"; 
		}
		
		echo
		self::jsCommon().
		$head;
		
		# --BEHAVIOR-- adminPageHTMLHead
		$core->callBehavior('adminPageHTMLHead');
		
		echo
		"</head>\n".
		'<body id="dotclear-admin" class="popup">'."\n".
		
		'<div id="top"><h1>'.DC_VENDOR_NAME.'</h1></div>'."\n";
		
		echo
		'<div id="wrapper">'."\n".
		'<div id="main">'."\n".
		'<div id="content">'."\n";
		
		if ($core->error->flag()) {
			echo
			'<div class="error"><strong>'.__('Errors:').'</strong>'.
			$core->error->toHTML().
			'</div>';
		}
	}
	
	public static function closePopup()
	{
		echo
		"</div>\n".		// End of #content
		"</div>\n".		// End of #main
		'<div id="footer"><p>&nbsp;</p></div>'."\n".
		"</div>\n".		// End of #wrapper
		'</body></html>';
	}

	public static function message($msg,$timestamp=true,$div=false,$echo=true)
	{
		global $core;
		
		$res = '';
		if ($msg != '') {
			$res = ($div ? '<div class="message">' : '').'<p'.($div ? '' : ' class="message"').'>'.
				($timestamp ? dt::str(__('%H:%M:%S:'),null,$core->auth->getInfo('user_tz')).' ' : '').$msg.
				'</p>'.($div ? '</div>' : '');
			if ($echo) {
				echo $res;
			}
		}
		return $res;
	}
	
	private static function debugInfo()
	{
		$global_vars = implode(', ',array_keys($GLOBALS));
		
		$res =
		'<div id="debug"><div>'.
		'<p>memory usage: '.memory_get_usage().' ('.files::size(memory_get_usage()).')</p>';
		
		if (function_exists('xdebug_get_profiler_filename'))
		{
			$res .= '<p>Elapsed time: '.xdebug_time_index().' seconds</p>';
			
			$prof_file = xdebug_get_profiler_filename();
			if ($prof_file) {
				$res .= '<p>Profiler file : '.xdebug_get_profiler_filename().'</p>';
			} else {
				$prof_url = http::getSelfURI();
				$prof_url .= (strpos($prof_url,'?') === false) ? '?' : '&';
				$prof_url .= 'XDEBUG_PROFILE';
				$res .= '<p><a href="'.html::escapeURL($prof_url).'">Trigger profiler</a></p>';
			}
			
			/* xdebug configuration:
			zend_extension = /.../xdebug.so
			xdebug.auto_trace = On
			xdebug.trace_format = 0
			xdebug.trace_options = 1
			xdebug.show_mem_delta = On
			xdebug.profiler_enable = 0
			xdebug.profiler_enable_trigger = 1
			xdebug.profiler_output_dir = /tmp
			xdebug.profiler_append = 0
			xdebug.profiler_output_name = timestamp
			*/
		}
		
		$res .=
		'<p>Global vars: '.$global_vars.'</p>'.
		'</div></div>';
		
		return $res;
	}
	
	public static function help($page,$index='')
	{
		# Deprecated but we keep this for plugins.
	}
	
	public static function helpBlock()
	{
		$args = func_get_args();
		if (empty($args)) {
			return;
		};
		
		global $__resources;
		if (empty($__resources['help'])) {
			return;
		}
		
		$content = '';
		foreach ($args as $v)
		{
			if (is_object($v) && isset($v->content)) {
				$content .= $v->content;
				continue;
			}
			
			if (!isset($__resources['help'][$v])) {
				continue;
			}
			$f = $__resources['help'][$v];
			if (!file_exists($f) || !is_readable($f)) {
				continue;
			}
			
			$fc = file_get_contents($f);
			if (preg_match('|<body[^>]*?>(.*?)</body>|ms',$fc,$matches)) {
				$content .= $matches[1];
			} else {
				$content .= $fc;
			}
		}
		
		if (trim($content) == '') {
			return;
		}
		
		echo
		'<div id="help"><hr /><div class="help-content clear"><h2>'.__('Help').'</h2>'.
		$content.
		'</div></div>';
	}
	
	public static function jsLoad($src)
	{
		$escaped_src = html::escapeHTML($src);
		if (!isset(self::$loaded_js[$escaped_src])) {
			self::$loaded_js[$escaped_src]=true;
			return '<script type="text/javascript" src="'.$escaped_src.'"></script>'."\n";
		}
	}
	
	public static function jsVar($n,$v)
	{
		return $n." = '".html::escapeJS($v)."';\n";
	}
	
	public static function jsCommon()
	{
		return
		self::jsLoad('js/jquery/jquery.js').
		self::jsLoad('js/jquery/jquery.biscuit.js').
		self::jsLoad('js/jquery/jquery.bgFade.js').
		self::jsLoad('js/jquery/jquery.constantfooter.js').
		self::jsLoad('js/common.js').
		self::jsLoad('js/prelude.js').
		
		'<script type="text/javascript">'."\n".
		"//<![CDATA[\n".
		self::jsVar('dotclear.nonce',$GLOBALS['core']->getNonce()).
		
		self::jsVar('dotclear.img_plus_src','images/expand.png').
		self::jsVar('dotclear.img_plus_alt',__('uncover')).
		self::jsVar('dotclear.img_minus_src','images/hide.png').
		self::jsVar('dotclear.img_minus_alt',__('hide')).
		self::jsVar('dotclear.img_menu_on','images/menu_on.png').
		self::jsVar('dotclear.img_menu_off','images/menu_off.png').
		
		self::jsVar('dotclear.msg.help',
			__('help')).
		self::jsVar('dotclear.msg.no_selection',
			__('no selection')).
		self::jsVar('dotclear.msg.select_all',
			__('select all')).
		self::jsVar('dotclear.msg.invert_sel',
			__('invert selection')).
		self::jsVar('dotclear.msg.website',
			__('Web site:')).
		self::jsVar('dotclear.msg.email',
			__('Email:')).
		self::jsVar('dotclear.msg.ip_address',
			__('IP address:')).
		self::jsVar('dotclear.msg.error',
			__('Error:')).
		self::jsVar('dotclear.msg.entry_created',
			__('Entry has been successfully created.')).
		self::jsVar('dotclear.msg.edit_entry',
			__('Edit entry')).
		self::jsVar('dotclear.msg.view_entry',
			__('view entry')).
		self::jsVar('dotclear.msg.confirm_delete_posts',
			__("Are you sure you want to delete selected entries (%s)?")).
		self::jsVar('dotclear.msg.confirm_delete_post',
			__("Are you sure you want to delete this entry?")).
		self::jsVar('dotclear.msg.confirm_spam_delete',
			__('Are you sure you want to delete all spams?')).
		self::jsVar('dotclear.msg.cannot_delete_users',
			__('Users with posts cannot be deleted.')).
		self::jsVar('dotclear.msg.confirm_delete_user',
			__('Are you sure you want to delete selected users (%s)?')).
		self::jsVar('dotclear.msg.confirm_extract_current',
			__('Are you sure you want to extract archive in current directory?')).
		self::jsVar('dotclear.msg.confirm_remove_attachment',
			__('Are you sure you want to remove attachment "%s"?')).
		self::jsVar('dotclear.msg.confirm_delete_lang',
			__('Are you sure you want to delete "%s" language?')).
		self::jsVar('dotclear.msg.confirm_delete_plugin',
			__('Are you sure you want to delete "%s" plugin?')).
		self::jsVar('dotclear.msg.use_this_theme',
			__('Use this theme')).
		self::jsVar('dotclear.msg.remove_this_theme',
			__('Remove this theme')).
		self::jsVar('dotclear.msg.confirm_delete_theme',
			__('Are you sure you want to delete "%s" theme?')).
		self::jsVar('dotclear.msg.zip_file_content',
			__('Zip file content')).
		self::jsVar('dotclear.msg.xhtml_validator',
			__('XHTML markup validator')).
		self::jsVar('dotclear.msg.xhtml_valid',
			__('XHTML content is valid.')).
		self::jsVar('dotclear.msg.xhtml_not_valid',
			__('There are XHTML markup errors.')).
		self::jsVar('dotclear.msg.confirm_change_post_format',
			__('You have unsaved changes. Switch post format will loose these changes. Proceed anyway?')).
		self::jsVar('dotclear.msg.load_enhanced_uploader',
			__('Loading enhanced uploader, please wait.')).
		"\n//]]>\n".
		"</script>\n";
	}
	
	public static function jsLoadIE7()
	{
		return
		'<!--[if lt IE 8]>'."\n".
		self::jsLoad('js/ie7/IE8.js').
		'<link rel="stylesheet" type="text/css" href="style/iesucks.css" />'."\n".
		'<![endif]-->'."\n";
	}
	
	public static function jsConfirmClose()
	{
		$args = func_get_args();
		if (count($args) > 0) {
			foreach ($args as $k => $v) {
				$args[$k] = "'".html::escapeJS($v)."'";
			}
			$args = implode(',',$args);
		} else {
			$args = '';
		}
		
		return
		self::jsLoad('js/confirm-close.js').
		'<script type="text/javascript">'."\n".
		"//<![CDATA[\n".
		"confirmClosePage = new confirmClose(".$args."); ".
		"confirmClose.prototype.prompt = '".html::escapeJS(__('You have unsaved changes.'))."'; ".
		"\n//]]>\n".
		"</script>\n";
	}
	
	public static function jsPageTabs($default=null)
	{
		if ($default) {
			$default = "'".html::escapeJS($default)."'";
		}
		
		return
		self::jsLoad('js/jquery/jquery.pageTabs.js').
		'<script type="text/javascript">'."\n".
		"//<![CDATA[\n".
		"\$(function() {\n".
		"	\$.pageTabs(".$default.");\n".
		"});\n".
		"\n//]]>\n".
		"</script>\n";
	}
	
	public static function jsModal()
	{
		return
		'<link rel="stylesheet" type="text/css" href="style/modal/modal.css" />'."\n".
		self::jsLoad('js/jquery/jquery.modal.js').
		'<script type="text/javascript">'."\n".
		"//<![CDATA[\n".
		self::jsVar('$.modal.prototype.params.loader_img','style/modal/loader.gif').
		self::jsVar('$.modal.prototype.params.close_img','style/modal/close.png').
		"\n//]]>\n".
		"</script>\n";
	}
	
	public static function jsColorPicker()
	{
		return
		'<link rel="stylesheet" type="text/css" href="style/farbtastic/farbtastic.css" />'."\n".
		self::jsLoad('js/jquery/jquery.farbtastic.js').
		self::jsLoad('js/color-picker.js');
	}
	
	public static function jsDatePicker()
	{
		return
		'<link rel="stylesheet" type="text/css" href="style/date-picker.css" />'."\n".
		self::jsLoad('js/date-picker.js').
		'<script type="text/javascript">'."\n".
		"//<![CDATA[\n".
		
		"datePicker.prototype.months[0] = '".html::escapeJS(__('January'))."'; ".
		"datePicker.prototype.months[1] = '".html::escapeJS(__('February'))."'; ".
		"datePicker.prototype.months[2] = '".html::escapeJS(__('March'))."'; ".
		"datePicker.prototype.months[3] = '".html::escapeJS(__('April'))."'; ".
		"datePicker.prototype.months[4] = '".html::escapeJS(__('May'))."'; ".
		"datePicker.prototype.months[5] = '".html::escapeJS(__('June'))."'; ".
		"datePicker.prototype.months[6] = '".html::escapeJS(__('July'))."'; ".
		"datePicker.prototype.months[7] = '".html::escapeJS(__('August'))."'; ".
		"datePicker.prototype.months[8] = '".html::escapeJS(__('September'))."'; ".
		"datePicker.prototype.months[9] = '".html::escapeJS(__('October'))."'; ".
		"datePicker.prototype.months[10] = '".html::escapeJS(__('November'))."'; ".
		"datePicker.prototype.months[11] = '".html::escapeJS(__('December'))."'; ".
		
		"datePicker.prototype.days[0] = '".html::escapeJS(__('Monday'))."'; ".
		"datePicker.prototype.days[1] = '".html::escapeJS(__('Tuesday'))."'; ".
		"datePicker.prototype.days[2] = '".html::escapeJS(__('Wednesday'))."'; ".
		"datePicker.prototype.days[3] = '".html::escapeJS(__('Thursday'))."'; ".
		"datePicker.prototype.days[4] = '".html::escapeJS(__('Friday'))."'; ".
		"datePicker.prototype.days[5] = '".html::escapeJS(__('Saturday'))."'; ".
		"datePicker.prototype.days[6] = '".html::escapeJS(__('Sunday'))."'; ".
		
		"datePicker.prototype.img_src = 'images/date-picker.png'; ".
		
		"datePicker.prototype.close_msg = '".html::escapeJS(__('close'))."'; ".
		"datePicker.prototype.now_msg = '".html::escapeJS(__('now'))."'; ".
		
		"\n//]]>\n".
		"</script>\n";
	}
	
	
	public static function jsToolMan()
	{
		return
		'<script type="text/javascript" src="js/tool-man/core.js"></script>'.
		'<script type="text/javascript" src="js/tool-man/events.js"></script>'.
		'<script type="text/javascript" src="js/tool-man/css.js"></script>'.
		'<script type="text/javascript" src="js/tool-man/coordinates.js"></script>'.
		'<script type="text/javascript" src="js/tool-man/drag.js"></script>'.
		'<script type="text/javascript" src="js/tool-man/dragsort.js"></script>'.
		'<script type="text/javascript" src="js/dragsort-tablerows.js"></script>';
	}
	
	public static function jsMetaEditor()
	{
		return
		'<script type="text/javascript" src="js/meta-editor.js"></script>';
	}
}
?>