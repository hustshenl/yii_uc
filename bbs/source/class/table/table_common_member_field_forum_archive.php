<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: table_common_member_field_forum_archive.php 27853 2012-02-15 10:24:24Z zhangguosheng $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_common_member_field_forum_archive extends table_common_member_field_forum
{
	public function __construct() {

		parent::__construct();
		$this->_table = 'common_member_field_forum_archive';
		$this->_pk    = 'uid';
	}

	public function fetch($id){
		return ($id = dintval($id)) ? DB::fetch_first('SELECT * FROM '.DB::table($this->_table).' WHERE '.DB::field($this->_pk, $id)) : array();
	}

	public function fetch_all($ids) {
		$data = array();
		if(($ids = dintval($ids))) {
			$query = DB::query('SELECT * FROM '.DB::table($this->_table).' WHERE '.DB::field($this->_pk, $ids));
			while($value = DB::fetch($query)) {
				$data[$value[$this->_pk]] = $value;
			}
		}
		return $data;
	}

	public function delete($val, $unbuffered = false) {
		return ($val = dintval($val, true)) && DB::delete($this->_table, DB::field($this->_pk, $val), null, $unbuffered);
	}
}

?>