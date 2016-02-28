<?php

class JO_Cache_Sql extends JO_Cache_Abstract {
	
	private $live_time = 3600;
	
	private $table = 'cache',
			$key = 'key_data',
			$data = 'data',
			$hash = 'hash',
			$time = 'date_add';
	
	
	public function __construct($options = array()) {
		parent::__construct($options);
		
		$this->deleteExpired();
	}
	
	public function setTime($value) {
		$this->time = $value;
		return $this;
	}
	
	public function getTime() {
		return $this->time;
	}
	
	public function setHash($value) {
		$this->hash = $value;
		return $this;
	}
	
	public function getHash() {
		return $this->hash;
	}
	
	public function setData($value) {
		$this->data = $value;
		return $this;
	}
	
	public function getData() {
		return $this->data;
	}
	
	public function setKey($value) {
		$this->key = $value;
		return $this;
	}
	
	public function getKey() {
		return $this->key;
	}
	
	public function setTable($value) {
		$this->table = $value;
		return $this;
	}
	
	public function getTable() {
		return $this->table;
	}
	
	public function setLiveTime($time) {
		$this->live_time = $time;
		return $this;
	}
	
	public function getLiveTime() {
		return $this->live_time;
	}
	
	public function store($key, $data) {
		$db = JO_Db::getDefaultAdapter();
		return $db->insertIgnore($this->getTable(),array(
			$this->getHash() => md5($key),
			$this->getKey() => $key,
			$this->getTime() => time(),
			$this->getData() => serialize($data)
		));
	}
	
	public function add($key, $data) {
		$db = JO_Db::getDefaultAdapter();
		return $db->insertIgnore($this->getTable(),array(
			$this->getHash() => md5($key),
			$this->getKey() => $key,
			$this->getTime() => time(),
			$this->getData() => serialize($data)
		));
	}
	
	public function get($key) {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from($this->getTable(), $this->getData())
					->where($this->getKey().' = ?',$key);
		$result = $db->fetchOne($query);
		if($result) {
			return unserialize($result);
		}
		return false;
	}
	
	public function clear() {
		$db = JO_Db::getDefaultAdapter();
		return $db->query("TRUNCATE TABLE `" . $this->getTable() . "`");
	}
	
	public function delete($key) {
		$db = JO_Db::getDefaultAdapter();
		return $db->delete($this->getTable(), array($this->getKey() . ' = ?' => $key));
	}
	
	public function deleteRegExp($regExp) {
		$db = JO_Db::getDefaultAdapter();
		return $db->delete($this->getTable(), array($this->getKey() . ' REGEXP ?' => $regExp));
	}
	
	public function deleteStrPos($pos) {
		$db = JO_Db::getDefaultAdapter();
		return $db->delete($this->getTable(), array($this->getKey() . ' LIKE ?' => '%' . $pos . '%'));
	}
	
	public function deleteExpired() {
		$db = JO_Db::getDefaultAdapter();
		return $db->delete($this->getTable(), array($this->getTime() . ' < ?' => (time() - $this->getLiveTime())));
	}
	
}