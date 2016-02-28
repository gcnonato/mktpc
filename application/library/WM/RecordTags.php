<?php

class WM_RecordTags {

	public static function addTags($filter_record_id = 0) {
		$db = JO_Db::getDefaultAdapter();
		
		$filter = array();
		if($filter_record_id) {
			$filter['filter_record_id'] = $filter_record_id;
		}
		
		$records = Model_Records::getRecords($filter);
		
		set_time_limit(0);
		
		$kwrd = array();
		
		if($records) {
			foreach($records AS $record) {
				
				$record_info = Model_Records::getRecord($record['record_id']);
				if(!$record_info) continue;
				
				$days = $record_info['days'];
				$nights = ($record_info['single_night'] ? 1 : $record_info['nights']);
				$city_title = $record_info['city_title'];
				
				$kwrd1 = array();
				
				$text_in = mb_strtolower(mb_substr($city_title, 0, 1, 'utf-8')) == 'в' ? self::translate('във') : self::translate('в');
    			if($record_info['record_type_id'] == JO_Registry::get('config_offers_record_type_id')) {
    				
    				$hotel_title = '';
					if(isset($record_info['record_id_hotel'])) {
						$hotel_title = $record_info['hotel_name'];
					}
    				$text_in_h = mb_strtolower(mb_substr($hotel_title, 0, 1, 'utf-8')) == 'в' ? self::translate('във') : self::translate('в');
	    			$hotel_description = '';
    				if(isset($record_info['record_id_hotel'])) {
						$hotel_info = Model_Records::getRecord($record_info['record_id_hotel']);
						if($hotel_info) { 
							$hotel_description = html_entity_decode($hotel_info['description'], ENT_QUOTES, 'UTF-8');
							$hotel_description .= html_entity_decode($hotel_info['nastaniavane'], ENT_QUOTES, 'UTF-8');
							$hotel_description .= html_entity_decode($hotel_info['hotelski_kompleks'], ENT_QUOTES, 'UTF-8');
							$hotel_description .= html_entity_decode($hotel_info['dopalnitelni_uslugi'], ENT_QUOTES, 'UTF-8');
							$hotel_description .= html_entity_decode($hotel_info['za_deca'], ENT_QUOTES, 'UTF-8');
							$hotel_description .= html_entity_decode($hotel_info['razvlechenia_sport'], ENT_QUOTES, 'UTF-8');
							$hotel_description .= html_entity_decode($hotel_info['morski_ski_spa'], ENT_QUOTES, 'UTF-8');
						}
					}
					
	    			$keywordssss = ($hotel_description ? $hotel_description : $record_info['description']);
	    			$keywordssss .= ' ' . $record_info['meta_title'];
	    			$keywordssss .= ' ' . $record_info['meta_description'];
	    			$keywordssss .= ' ' . html_entity_decode($record_info['description'], ENT_QUOTES, 'UTF-8');
					$keywordssss .= ' ' . html_entity_decode($record_info['nastaniavane'], ENT_QUOTES, 'UTF-8');
					$keywordssss .= ' ' . html_entity_decode($record_info['hotelski_kompleks'], ENT_QUOTES, 'UTF-8');
					$keywordssss .= ' ' . html_entity_decode($record_info['dopalnitelni_uslugi'], ENT_QUOTES, 'UTF-8');
					$keywordssss .= ' ' . html_entity_decode($record_info['za_deca'], ENT_QUOTES, 'UTF-8');
					$keywordssss .= ' ' . html_entity_decode($record_info['razvlechenia_sport'], ENT_QUOTES, 'UTF-8');
					$keywordssss .= ' ' . html_entity_decode($record_info['morski_ski_spa'], ENT_QUOTES, 'UTF-8');
	    			
	    			
	    			$params = array();
		    		$params['content'] = html_entity_decode($keywordssss, ENT_QUOTES, 'UTF-8'); //page content
		    		
		    		$keywords = new WM_Keywords($params);
		    		$record_info['meta_keywords'] = $keywords->get_keywords() . ', ' . $city_title;
		    		$record_info['meta_keywords'] .= ', ' . self::translate('оферти') . ' ' . $city_title . ', ' . self::translate('пакети') . ' ' . $city_title;
		    		
		    		
		    		$record_info['meta_keywords'] = $record_info['meta_keywords'] . ', ' . WM_Keywords::translate($record_info['meta_keywords']);
			    		
			    	$kwrd = explode(',', $record_info['meta_keywords']);
			    	$kwrd1 = array_map('trim', $kwrd);
			    	
    			} elseif($record_info['record_type_id'] == JO_Registry::get('config_hotel_record_type_id')) {
    			
    				$params = array();
    				$hotel_description = html_entity_decode($record_info['description'], ENT_QUOTES, 'UTF-8');
					$hotel_description .= html_entity_decode($record_info['nastaniavane'], ENT_QUOTES, 'UTF-8');
					$hotel_description .= html_entity_decode($record_info['hotelski_kompleks'], ENT_QUOTES, 'UTF-8');
					$hotel_description .= html_entity_decode($record_info['dopalnitelni_uslugi'], ENT_QUOTES, 'UTF-8');
					$hotel_description .= html_entity_decode($record_info['za_deca'], ENT_QUOTES, 'UTF-8');
					$hotel_description .= html_entity_decode($record_info['razvlechenia_sport'], ENT_QUOTES, 'UTF-8');
					$hotel_description .= html_entity_decode($record_info['morski_ski_spa'], ENT_QUOTES, 'UTF-8');
		    		$params['content'] = $hotel_description; //page content
		    		
		    		$keywords = new WM_Keywords($params);
		    		$record_info['meta_keywords'] = $keywords->get_keywords() . ', ' . $city_title;
		    		$related_sum = Model_Records::getTotalRelatedOffersForViewHotel($record['record_id']);
		    		if($related_sum) {
		    			$record_info['meta_keywords'] .= ', ' . self::translate('оферти') . ' ' . $record_info['title'] . ', ' . self::translate('пакети') . ' ' . $record_info['title'];
		    			$record_info['meta_keywords'] .= ', ' . self::translate('оферти') . ' ' . $city_title . ', ' . self::translate('пакети') . ' ' . $city_title;
		    		}
		    		
		    		$record_info['meta_keywords'] = $record_info['meta_keywords'] . ', ' . WM_Keywords::translate($record_info['meta_keywords']);
		    		
			    	$kwrd = explode(',', $record_info['meta_keywords']);
			    	$kwrd1 = array_map('trim', $kwrd);
    				
    			} elseif($record_info['record_type_id'] == JO_Registry::get('config_trips_record_type_id')) {
    				
    			}
    			
    			if($kwrd1) {
    				foreach($kwrd1 AS $row) {
    					if($row) {
//    						if(preg_match_all('/[a-z0-9а-яА-Я\- \.]+/ium', $row, $match)) {
//    							foreach($match[0] AS $row1) {
//    								$db->insertIgnore('records_tags', array(
//		    							'record_id' => $record['record_id'],
//		    							'language_id' => (int)JO_Registry::get('config_language_id'),
//		    							'tag' => $row1
//		    						));
//    							}
//    						} else {
	    						$db->insertIgnore('records_tags', array(
	    							'record_id' => $record['record_id'],
	    							'language_id' => (int)JO_Registry::get('config_language_id'),
	    							'tag' => $row
	    						));
//    						}
    					}
    				}
    			}
    			
    			
				
			}
		}
	}
	
	public function translate($string) {
		return JO_Translate::getInstance()->translate($string);
	}
	
}

?>