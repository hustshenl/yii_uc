<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: search_sort.php 26101 2011-12-01 08:28:32Z monkey $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}
if(!empty($searchid)) {
	$page = max(1, intval($_GET['page']));
	$start_limit = ($page - 1) * $_G['tpp'];

	$index = C::t('common_searchindex')->fetch($searchid);

	if($index['threadsortid'] != $sortid) {
		showmessage('search_id_invalid');
	}

	$threadlist = $typelist = $resultlist = $_G['forum_optionlist'] = array();
	foreach(C::t('forum_thread')->fetch_all_by_tid_fid_displayorder(explode(',', $index['tids']), null, 0, 'dateline', $start_limit, $_G['tpp']) as $info) {
		$threadlist[$info['tid']]['icon'] = isset($_G['cache']['icons'][$info['iconid']]) ? '<img src="'.STATICURL.'image/forum/icon/'.$_G['cache']['icons'][$info['iconid']].'" alt="Icon'.$info['iconid'].'" class="icon" />' : '&nbsp;';
		$threadlist[$info['tid']]['dateline'] = dgmdate($info['dateline'], 'u');
		$threadlist[$info['tid']]['subject'] = $info['subject'];
	}

	@include_once DISCUZ_ROOT.'./data/cache/forum_threadsort_'.$index['threadsortid'].'.php';

	foreach(C::t('forum_typeoptionvar')->fetch_all_by_tid_optionid(explode(',', $index['tids'])) as $info) {
		if($_G['forum_dtype'][$info['optionid']]['search']) {
			$optionid = $info['optionid'];
			$identifier = $_G['forum_dtype'][$optionid]['identifier'];
			$unit = $_G['forum_dtype'][$optionid]['unit'];
			$typelist[$info['tid']][$optionid]['value'] = $info['value'];
			$_G['forum_optionlist'][$identifier] = $_G['forum_dtype'][$optionid]['title'].($unit ? "($unit)" : '');
		}
	}

	$_G['forum_optionlist'] = $_G['forum_optionlist'] ? array_unique($_G['forum_optionlist']) : '';

	$choiceshow = array();
	foreach($threadlist as $tid => $thread) {
		$resultlist[$tid]['icon'] = $thread['icon'];
		$resultlist[$tid]['subject'] = $thread['subject'];
		$resultlist[$tid]['dateline'] = $thread['dateline'];
		if(is_array($typelist[$tid])) {
			foreach($typelist[$tid] as $optionid => $value) {
				$identifier = $_G['forum_dtype'][$optionid]['identifier'];
				if(in_array($_G['forum_dtype'][$optionid]['type'], array('select', 'radio'))) {
					$resultlist[$tid]['option'][$identifier] = $_G['forum_dtype'][$optionid]['choices'][$value['value']];
				} elseif($_G['forum_dtype'][$optionid]['type'] == 'checkbox') {
					foreach(explode("\t", $value['value']) as $choiceid) {
						$choiceshow[$tid] .= $_G['forum_dtype'][$optionid]['choices'][$choiceid].'&nbsp;';
					}
					$resultlist[$tid]['option'][$identifier] = $choiceshow[$tid];
				} elseif($_G['forum_dtype'][$optionid]['type'] == 'image') {
					$maxwidth = $_G['forum_dtype'][$optionid]['maxwidth'] ? 'width="'.$_G['forum_dtype'][$optionid]['maxwidth'].'"' : '';
					$maxheight = $_G['forum_dtype'][$optionid]['maxheight'] ? 'height="'.$_G['forum_dtype'][$optionid]['maxheight'].'"' : '';
					$resultlist[$tid]['option'][$identifier] = $_G['forum_optiondata'][$optionid] ? "<a href=\"".$_G['forum_optiondata'][$optionid]."\" target=\"_blank\"><img src=\"$value[value]\"  $maxwidth $maxheight border=\"0\"></a>" : '';
				} elseif($_G['forum_dtype'][$optionid]['type'] == 'url') {
					$resultlist[$tid]['option'][$identifier] = $_G['forum_optiondata'][$optionid] ? "<a href=\"$value[value]\" target=\"_blank\">$value[value]</a>" : '';
				} else {
					$resultlist[$tid]['option'][$identifier] = $value['value'];
				}
			}
		}
	}

	$colspan = count($_G['forum_optionlist']) + 2;
	$multipage = multi($index['threads'], $_G['tpp'], $page, "forum.php?mod=search&searchid=$searchid&srchtype=threadsort&sortid=$index[threadsortid]&searchsubmit=yes");
	$url_forward = 'forum.php?mod=search&'.$_SERVER['QUERY_STRING'];
	include template('forum/search_sort');

} else {

	!($_G['group']['exempt'] & 2) && checklowerlimit('search');

	$forumsarray = array();
	if(!empty($srchfid)) {
		foreach((is_array($srchfid) ? $srchfid : explode('_', $srchfid)) as $forum) {
			if($forum = intval(trim($forum))) {
				$forumsarray[] = $forum;
			}
		}
	}

	$comma = '';
	$fids = array();
	foreach($_G['cache']['forums'] as $fid => $forum) {
		if($forum['type'] != 'group' && (!$forum['viewperm'] && $_G['group']['readaccess']) || ($forum['viewperm'] && forumperm($forum['viewperm']))) {
			if(!$forumsarray || in_array($fid, $forumsarray)) {
				$fids[] = $fid;
			}
		}
	}

	$srchoption = $tab = '';
	if($searchoption && is_array($searchoption)) {
		foreach($searchoption as $optionid => $option) {
			$srchoption .= $tab.$optionid;
			$tab = "\t";
		}
	}

	$searchstring = 'type|'.addslashes($srchoption);
	$searchindex = array('id' => 0, 'dateline' => '0');

	foreach(C::t('common_searchindex')->fetch_all_search($_G['setting']['searchctrl'], $_G['clientip'], $_G['uid'], $_G['timestamp'], $searchstring) as $index) {
		if($index['indexvalid'] && $index['dateline'] > $searchindex['dateline']) {
			$searchindex = array('id' => $index['searchid'], 'dateline' => $index['dateline']);
			break;
		} elseif($index['flood']) {
			showmessage('search_ctrl', "forum.php?mod=search&srchtype=threadsort&sortid=".$_GET['selectsortid']."&srchfid=$_G[fid]", array('searchctrl' => $_G['setting']['searchctrl']));
		}
	}

	if($searchindex['id']) {

		$searchid = $searchindex['id'];

	} else {

		if((!$searchoption || !is_array($searchoption)) && !$_GET['selectsortid']) {
			showmessage('search_threadtype_invalid', "forum.php?mod=search&srchtype=threadsort&sortid=".$_GET['selectsortid']."&srchfid=$_G[fid]");
		} elseif(isset($srchfid) && $srchfid != 'all' && !(is_array($srchfid) && in_array('all', $srchfid)) && empty($forumsarray)) {
			showmessage('search_forum_invalid', "forum.php?mod=search&srchtype=threadsort&sortid=".$_GET['selectsortid']."&srchfid=$_G[fid]");
		} elseif(empty($fids)) {
			showmessage('group_nopermission', NULL, array('grouptitle' => $_G['group']['grouptitle']), array('login' => 1));
		}

		if($_G['setting']['maxspm']) {
			if(C::t('common_searchindex')->count_by_dateline($_G['timestamp']) >= $_G['setting']['maxspm']) {
				showmessage('search_toomany', 'forum.php?mod=search', array('maxspm' => $_G['setting']['maxspm']));
			}
		}

		$_GET['selectsortid'] = intval($_GET['selectsortid']);
		@include_once DISCUZ_ROOT.'./data/cache/forum_threadsort_'.$_GET['selectsortid'].'.php';

		$sqlsrch = $or = '';
		if(!empty($searchoption) && is_array($searchoption)) {
			foreach($searchoption as $optionid => $option) {
				$fieldname = $_G['forum_dtype'][$optionid]['identifier'] ? $_G['forum_dtype'][$optionid]['identifier'] : 1;
				if($option['value']) {
					if(in_array($option['type'], array('number', 'radio', 'select'))) {
						$option['value'] = intval($option['value']);
						$exp = '=';
						if($option['condition']) {
							$exp = $option['condition'] == 1 ? '>' : '<';
						}
						$sql = "$fieldname$exp'$option[value]'";
					} elseif($option['type'] == 'checkbox') {
						$sql = "$fieldname LIKE '%\t".(implode("\t", $option['value']))."\t%'";
					} else {
						$sql = "$fieldname LIKE '%$option[value]%'";
					}
					$sqlsrch .= $and."$sql ";
					$and = 'AND ';
				}
			}
		}

		$threads = 0;
		$tids = C::t('forum_optionvalue')->fetch_all_tid($_GET['selectsortid'], $sqlsrch ? 'WHERE '.$sqlsrch : '');


		if($fids) {
			foreach(C::t('forum_thread')->fetch_all_by_tid_fid_displayorder(explode(',', $tids), $fids, null, 'dateline', $_G['setting']['maxsearchresults']) as $post) {
				if($thread['closed'] <= 1) {
					$tids .= ','.$post['tid'];
					$threads++;
				}
			}
		}

		$searchid = C::t('common_searchindex')->insert(array(
			'keywords' => $keywords,
			'searchstring' => $searchstring,
			'useip' => $_G['clientip'],
			'uid' => $_G['uid'],
			'dateline' => $_G['timestamp'],
			'expiration' => $expiration,
			'threads' => $threads,
			'threadsortid' => $_GET['selectsortid'],
			'tids' => $tids
		), true);

		!($_G['group']['exempt'] & 2) && updatecreditbyaction('search');
	}

	showmessage('search_redirect', "forum.php?mod=search&searchid=$searchid&srchtype=threadsort&sortid=".$_GET['selectsortid']."&searchsubmit=yes");

}

?>