<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) 2003-2013 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------

if (!defined('DC_RC_PATH')) { return; }

class defaultWidgets
{
	public static function search($w)
	{
		global $core;

		if (($w->homeonly == 1 && $core->url->type != 'default') ||
			($w->homeonly == 2 && $core->url->type == 'default')) {
			return;
		}

		$value = isset($GLOBALS['_search']) ? html::escapeHTML($GLOBALS['_search']) : '';

		return
		($w->content_only ? '' : '<div id="search"'.($w->class ? ' class="'.html::escapeHTML($w->class).'"' : '').'>').
		($w->title ? '<h2><label for="q">'.html::escapeHTML($w->title).'</label></h2>' : '').
		'<form action="'.$core->blog->url.'" method="get">'.
		'<fieldset>'.
		'<p><input type="text" size="10" maxlength="255" id="q" name="q" value="'.$value.'" /> '.
		'<input type="submit" class="submit" value="ok" /></p>'.
		'</fieldset>'.
		'</form>'.
		($w->content_only ? '' : '</div>');
	}

	public static function navigation($w)
	{
		global $core;

		if (($w->homeonly == 1 && $core->url->type != 'default') ||
			($w->homeonly == 2 && $core->url->type == 'default')) {
			return;
		}

		$res =
		($w->content_only ? '' : '<div id="topnav"'.($w->class ? ' class="'.html::escapeHTML($w->class).'"' : '').'>').
		($w->title ? '<h2>'.html::escapeHTML($w->title).'</h2>' : '').
		'<ul>';

		if ($core->url->type != 'default') {
			$res .=
			'<li class="topnav-home">'.
			'<a href="'.$core->blog->url.'">'.__('Home').'</a>'.
			'<span> - </span></li>';
		}

		$res .=
		'<li class="topnav-arch">'.
		'<a href="'.$core->blog->url.$core->url->getURLFor("archive").'">'.
		__('Archives').'</a></li>'.
		'</ul>'.
		($w->content_only ? '' : '</div>');

		return $res;
	}

	public static function categories($w)
	{
		global $core, $_ctx;

		if (($w->homeonly == 1 && $core->url->type != 'default') ||
			($w->homeonly == 2 && $core->url->type == 'default')) {
			return;
		}

		$rs = $core->blog->getCategories(array('post_type'=>'post','without_empty'=> !$w->with_empty));
		if ($rs->isEmpty()) {
			return;
		}

		$res =
		($w->content_only ? '' : '<div class="categories'.($w->class ? ' '.html::escapeHTML($w->class) : '').'">').
		($w->title ? '<h2>'.html::escapeHTML($w->title).'</h2>' : '');

		$ref_level = $level = $rs->level-1;
		while ($rs->fetch())
		{
			$class = '';
			if (($core->url->type == 'category' && $_ctx->categories instanceof record && $_ctx->categories->cat_id == $rs->cat_id)
			|| ($core->url->type == 'post' && $_ctx->posts instanceof record && $_ctx->posts->cat_id == $rs->cat_id)) {
				$class = ' class="category-current"';
			}

			if ($rs->level > $level) {
				$res .= str_repeat('<ul><li'.$class.'>',$rs->level - $level);
			} elseif ($rs->level < $level) {
				$res .= str_repeat('</li></ul>',-($rs->level - $level));
			}

			if ($rs->level <= $level) {
				$res .= '</li><li'.$class.'>';
			}

			$res .=
			'<a href="'.$core->blog->url.$core->url->getURLFor('category', $rs->cat_url).'">'.
			html::escapeHTML($rs->cat_title).'</a>'.
			($w->postcount ? ' <span>('.($w->subcatscount ? $rs->nb_total : $rs->nb_post).')</span>' : '');


			$level = $rs->level;
		}

		if ($ref_level - $level < 0) {
			$res .= str_repeat('</li></ul>',-($ref_level - $level));
		}
		$res .= ($w->content_only ? '' : '</div>');

		return $res;
	}

	public static function bestof($w)
	{
		global $core;

		if (($w->homeonly == 1 && $core->url->type != 'default') ||
			($w->homeonly == 2 && $core->url->type == 'default')) {
			return;
		}

		$params = array(
			'post_selected'	=> true,
			'no_content'		=> true,
			'order'			=> 'post_dt '.strtoupper($w->orderby)
		);

		$rs = $core->blog->getPosts($params);

		if ($rs->isEmpty()) {
			return;
		}

		$res =
		($w->content_only ? '' : '<div class="selected'.($w->class ? ' '.html::escapeHTML($w->class) : '').'">').
		($w->title ? '<h2>'.html::escapeHTML($w->title).'</h2>' : '').
		'<ul>';

		while ($rs->fetch()) {
			$res .= ' <li><a href="'.$rs->getURL().'">'.html::escapeHTML($rs->post_title).'</a></li> ';
		}

		$res .= '</ul>'.($w->content_only ? '' : '</div>');

		return $res;
	}

	public static function langs($w)
	{
		global $core, $_ctx;

		if (($w->homeonly == 1 && $core->url->type != 'default' && $core->url->type != 'lang') ||
			($w->homeonly == 2 && ($core->url->type == 'default' || $core->url->type == 'lang'))) {
			return;
		}

		$rs = $core->blog->getLangs();

		if ($rs->count() <= 1) {
			return;
		}

		$langs = l10n::getISOcodes();
		$res =
		($w->content_only ? '' : '<div class="langs'.($w->class ? ' '.html::escapeHTML($w->class) : '').'">').
		($w->title ? '<h2>'.html::escapeHTML($w->title).'</h2>' : '').
		'<ul>';

		while ($rs->fetch())
		{
			$l = ($_ctx->cur_lang == $rs->post_lang) ? '<strong>%s</strong>' : '%s';

			$lang_name = isset($langs[$rs->post_lang]) ? $langs[$rs->post_lang] : $rs->post_lang;

			$res .=
			' <li>'.
			sprintf($l,
				'<a href="'.$core->blog->url.$core->url->getURLFor('lang',$rs->post_lang).'" '.
				'class="lang-'.$rs->post_lang.'">'.
				$lang_name.'</a>').
			' </li>';
		}

		$res .= '</ul>'.($w->content_only ? '' : '</div>');

		return $res;
	}

	public static function subscribe($w)
	{
		global $core;

		if (($w->homeonly == 1 && $core->url->type != 'default') ||
			($w->homeonly == 2 && $core->url->type == 'default')) {
			return;
		}

		$type = ($w->type == 'atom' || $w->type == 'rss2') ? $w->type : 'rss2';
		$mime = $type == 'rss2' ? 'application/rss+xml' : 'application/atom+xml';

		$p_title = __('This blog\'s entries %s feed');
		$c_title = __('This blog\'s comments %s feed');

		$res =
		($w->content_only ? '' : '<div class="syndicate'.($w->class ? ' '.html::escapeHTML($w->class) : '').'">').
		($w->title ? '<h2>'.html::escapeHTML($w->title).'</h2>' : '').
		'<ul>';

		$res .=
		'<li><a type="'.$mime.'" '.
		'href="'.$core->blog->url.$core->url->getURLFor('feed', $type).'" '.
		'title="'.sprintf($p_title,($type == 'atom' ? 'Atom' : 'RSS')).'" class="feed">'.
		__('Entries feed').'</a></li>';

		if ($core->blog->settings->system->allow_comments || $core->blog->settings->system->allow_trackbacks)
		{
			$res .=
			'<li><a type="'.$mime.'" '.
			'href="'.$core->blog->url.$core->url->getURLFor('feed',$type.'/comments').'" '.
			'title="'.sprintf($c_title,($type == 'atom' ? 'Atom' : 'RSS')).'" class="feed">'.
			__('Comments feed').'</a></li>';
		}

		$res .= '</ul>'.($w->content_only ? '' : '</div>');

		return $res;
	}

	public static function feed($w)
	{
		if (!$w->url) {
			return;
		}

		global $core;

		if (($w->homeonly == 1 && $core->url->type != 'default') ||
			($w->homeonly == 2 && $core->url->type == 'default')) {
			return;
		}

		$limit = abs((integer) $w->limit);

		try {
			$feed = feedReader::quickParse($w->url,DC_TPL_CACHE);
			if ($feed == false || count($feed->items) == 0) {
				return;
			}
		} catch (Exception $e) {
			return;
		}

		$res =
		($w->content_only ? '' : '<div class="feed'.($w->class ? ' '.html::escapeHTML($w->class) : '').'">').
		($w->title ? '<h2>'.html::escapeHTML($w->title).'</h2>' : '').
		'<ul>';

		$i = 0;
		foreach ($feed->items as $item) {
			$title = isset($item->title) && strlen(trim($item->title)) ? $item->title : '';
			$link = isset($item->link) && strlen(trim($item->link)) ? $item->link : '';

			if (!$link && !$title) {
				continue;
			}

			if (!$title) {
				$title = substr($link,0,25).'...';
			}

			$li = $link ? '<a href="'.html::escapeHTML($item->link).'">'.$title.'</a>' : $title;
			$res .= ' <li>'.$li.'</li> ';
			$i++;
			if ($i >= $limit) {
				break;
			}
		}

		$res .= '</ul>'.($w->content_only ? '' : '</div>');

		return $res;
	}

	public static function text($w)
	{
		global $core;

		if (($w->homeonly == 1 && $core->url->type != 'default') ||
			($w->homeonly == 2 && $core->url->type == 'default')) {
			return;
		}

		$res =
		($w->content_only ? '' : '<div class="text'.($w->class ? ' '.html::escapeHTML($w->class) : '').'">').
		($w->title ? '<h2>'.html::escapeHTML($w->title).'</h2>' : '').
		$w->text.
		($w->content_only ? '' : '</div>');

		return $res;
	}

	public static function lastposts($w)
	{
		global $core;

		if (($w->homeonly == 1 && $core->url->type != 'default') ||
			($w->homeonly == 2 && $core->url->type == 'default')) {
			return;
		}

		$params['limit'] = abs((integer) $w->limit);
		$params['order'] = 'post_dt desc';
		$params['no_content'] = true;

		if ($w->category)
		{
			if ($w->category == 'null') {
				$params['sql'] = ' AND P.cat_id IS NULL ';
			} elseif (is_numeric($w->category)) {
				$params['cat_id'] = (integer) $w->category;
			} else {
				$params['cat_url'] = $w->category;
			}
		}

		if ($w->tag)
		{
			$params['meta_id'] = $w->tag;
			$rs = $core->meta->getPostsByMeta($params);
		}
		else
		{
			$rs = $core->blog->getPosts($params);
		}

		if ($rs->isEmpty()) {
			return;
		}

		$res =
		($w->content_only ? '' : '<div class="lastposts'.($w->class ? ' '.html::escapeHTML($w->class) : '').'">').
		($w->title ? '<h2>'.html::escapeHTML($w->title).'</h2>' : '').
		'<ul>';

		while ($rs->fetch()) {
			$res .= '<li><a href="'.$rs->getURL().'">'.
			html::escapeHTML($rs->post_title).'</a></li>';
		}

		$res .= '</ul>'.($w->content_only ? '' : '</div>');

		return $res;
	}

	public static function lastcomments($w)
	{
		global $core;

		if (($w->homeonly == 1 && $core->url->type != 'default') ||
			($w->homeonly == 2 && $core->url->type == 'default')) {
			return;
		}

		$params['limit'] = abs((integer) $w->limit);
		$params['order'] = 'comment_dt desc';
		$rs = $core->blog->getComments($params);

		if ($rs->isEmpty()) {
			return;
		}

		$res = ($w->content_only ? '' : '<div class="lastcomments'.($w->class ? ' '.html::escapeHTML($w->class) : '').'">').
		($w->title ? '<h2>'.html::escapeHTML($w->title).'</h2>' : '').
		'<ul>';

		while ($rs->fetch())
		{
			$res .= '<li class="'.
			((boolean)$rs->comment_trackback ? 'last-tb' : 'last-comment').
			'"><a href="'.$rs->getPostURL().'#c'.$rs->comment_id.'">'.
			html::escapeHTML($rs->post_title).' - '.
			html::escapeHTML($rs->comment_author).
			'</a></li>';
		}

		$res .= '</ul>'.($w->content_only ? '' : '</div>');

		return $res;
	}
}
