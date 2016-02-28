<?php

class Model_Pagination extends JO_Pagination {

	protected $url;
//	protected $text_first = '&laquo;';
//	protected $text_last = '&raquo;';
//	protected $text_next = '&rsaquo;';
//	protected $text_prev = '&lsaquo;';
	
	public function setUrl($url) {
		$this->url = $url;
	}
	
	public function setText(array $data) {
		foreach($data AS $key => $value) {
			$this->{$key} = $value;
		}
		return $this;
	}
	
	public function render() {
		$results = parent::render();
		
		if($this->num_pages < 2) return '';
		
		$output = array();
		
		if($this->page > 1) {
			$output[] = '<a href="' . str_replace('{page}', ($this->page - 1), $this->url) . '">' . $this->text_prev . '</a>';
		} else {
			$output[] = $this->text_prev;
		}
		if($this->page > 3) {
			$output[] = '..';
		}
		
		foreach($results AS $result) {
			if($result == $this->page) {
				$output[] = $result;
			} else {
				$output[] = '<a href="' . str_replace('{page}', $result, $this->url) . '">' . $result . '</a>';
			}
		}
		
		if($this->num_pages > 5 && $this->page > 3 && $this->page < ($this->num_pages - 2)) {
			$output[] = '..';
		}
		
		$output[] = '<input type="text" />';
		$output[] = '{of}';
		$output[] = $this->num_pages;
		
		if ($this->page < $this->num_pages) {
			$output[] = '<a href="' . str_replace('{page}', ($this->page + 1), $this->url) . '">' . $this->text_next . '</a>';
		} else {
			$output[] = $this->text_next;
		}
		
		return implode('&nbsp;', $output);
	}
	
}

?>