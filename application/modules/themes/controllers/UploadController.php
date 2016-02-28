<?php

class UploadController extends JO_Action {

	/* SELECT CATEGORIES */
	public function indexAction() {
		$request = $this->getRequest();
		
		if(!JO_Session::get('user_id')) {
	        JO_Session::set('msg_error', $this->translate('You must be logged to change your profile'));
	        $this->redirect(WM_Router::create($request->getBaseUrl() . '?controller=users&action=login'));
	    }

		if(JO_Session::get('quiz') != 'true') {
			$this->redirect(WM_Router::create($request->getBaseUrl() .'?controller=pages&page_id='. JO_Registry::forceGet('page_upload_item')));
		}
		
		if(JO_Session::get('msg_error')) {
			$this->view->msg_error = JO_Session::get('msg_error');
			JO_Session::clear('msg_error');
		}
		
		$this->view->crumbs = array(
			array(
				'name' => $this->translate('Home'),
				'href' => $request->getBaseUrl()
			),
			array(
				'name' => $this->translate('Upload')
			)
		);
		
		$this->getLayout()->meta_title = $this->translate('Item upload');
    	$this->getLayout()->meta_description = $this->translate('Item upload');
		
		$user = Model_Users::getByUsername(JO_Session::get('username'));
		$this->view->author_header = Helper_Author::authorHeader($user);
		
		$categories = Model_Categories::getMain();		
		if($categories) {
			$this->view->categories = array(
				array(
					'href' => '',
					'name' => $this->translate('Please select category')
				)
			);
			
			foreach($categories as $category) {
				$this->view->categories[] = array(
					'href' => WM_Router::create($request->getBaseUrl() .'?controller=upload&action=form&category_id='. $category['id']),
					'name' => $category['name']
				);
			} 
		}
		
		$this->view->total_sales_cnt = JO_Session::get('sales');
		$this->view->total_balance = WM_Currency::format(JO_Session::get('total'));
		$this->view->percent = Model_Percentes::getPercentRow($user);
		
		$this->view->children = array();
    	$this->view->children['header_part'] = 'layout/header_part';
		$this->view->children['footer_part'] = 'layout/footer_part';
	}

	/* FORM */
	public function formAction() {
		$request = $this->getRequest();
	    
	    if(!JO_Session::get('user_id')) {
	        JO_Session::set('msg_error', $this->translate('You must be logged to upload an item'));
	        $this->redirect(WM_Router::create($request->getBaseUrl() . '?controller=users&action=login'));
	    }
	    
	    if(JO_Session::get('quiz') != 'true') {
	        JO_Session::set('msg_error', $this->translate('In order to upload your files for sale first you have to pass our quiz.'));
	        $this->redirect(WM_Router::create($request->getBaseUrl() . '?controller=quiz'));
	    }
		
		if($request->getRequest('form')) {
			$category_id = $request->getRequest('form');
		}

		$redir_link = WM_Router::create($request->getBaseUrl() . '?controller=upload');
		if(!$category_id) {
			$this->redirect($redir_link);
		}
		
		$this->getLayout()->meta_title = $this->translate('Item upload');
    	$this->getLayout()->meta_description = $this->translate('Item upload');
		
	    $mainCategories = Model_Categories::getMain();
	    
		$cnt = count($mainCategories);
		for($i = 0; $i < $cnt; $i++) {
			$mainCategories[$i]['href'] = WM_Router::create($request->getBaseUrl() .'?controller=upload&action=get_categories&category_id='. $mainCategories[$i]['id']);
		}
		
	    $category_info = Model_Categories::get($category_id);
	   	
		$has_category = JO_Array::multi_array_search($mainCategories, 'id', $category_id);
		
	    if(!$category_info) {
	    	JO_Session::set('msg_error', $this->translate('You have choosen a non existing category'));
    		$this->redirect($redir_link);
	    } elseif(empty($has_category)) {
    		JO_Session::set('msg_error', $this->translate('You have choosen a non existing category'));
    		$this->redirect($redir_link);
    	}
    	
		if(JO_Session::get('msg_error')) {
			$this->view->error = JO_Session::get('msg_error');
			JO_Session::clear('msg_error');
		}
		
		if(JO_Session::get('data')) {
			$this->view->d = JO_Session::get('data');
			JO_Session::clear('data');
		}
	
		$fileTypes = JO_Registry::get('upload_theme');
	   
	    $types = '';
	    if($fileTypes) {
	        foreach($fileTypes as $type) {
	           $tp = explode(',', $type);
	           foreach($tp as $t) {
	               $types .= '*.'.$t.';';
	           }  
	        }
	    }
	   
	   	$this->view->sel_category = $category_id;				
	   
	    $this->view->mainCategories = $mainCategories;
		$allCategories = Model_Categories::getWithChilds();
		
		$categoriesSelect = Model_Categories::generateSelect($allCategories, $category_id, $category_id);
		
		if($categoriesSelect) {
	    	$categories = explode('|', $categoriesSelect);
			foreach($categories as $category) {
				if(!empty($category)) {
					$c = explode('>', $category);
					$this->view->categoriesSelect[] = array(
						'id' => $c[0],
						'name' => trim($c[1])
					);
				}	
			}
		}
		
	    $this->view->fileTypes = $types;
      	$this->view->attributes = Model_Attributes::getAllWithCategories("attributes_categories.categories LIKE '%,".(int) $category_id.",%'");

		$fileTypes_allow = JO_Registry::get('upload_theme');
		
    	$allow_archives = array();
    	if(isset($fileTypes_allow['archives'])) {
			$ew = explode(',', $fileTypes_allow['archives']);
			foreach($ew AS $ar) {
				$allow_archives[] = '.'.strtolower($ar);
			}
		}
		
		$allow_images = array();
		if(isset($fileTypes_allow['images'])) {
			$ew = explode(',', $fileTypes_allow['images']);
			foreach($ew AS $ar) {
				$allow_images[] = '.'.strtolower($ar);
			}
		}
		
        $this->view->uploaded_files = JO_Session::get('uploaded_files');
		$this->view->uploaded_arhives = JO_Session::get('uploaded_arhives');
		
		$this->view->crumbs = array(
			array(
				'name' => $this->translate('Home'),
				'href' => $request->getBaseUrl()
			),
			array(
				'name' => $this->translate('Upload')
			)
		);
		
		$user = Model_Users::getByUsername(JO_Session::get('username'));
		$this->view->author_header = Helper_Author::authorHeader($user);
			
    	$this->view->action_upload = WM_Router::create($request->getBaseUrl() . '?controller=upload&action=upload');
  		$this->view->autocomplete = WM_Router::create($request->getBaseUrl() . '?controller=items&action=auto');
		
		$this->view->total_sales_cnt = JO_Session::get('sales');
		$this->view->total_balance = WM_Currency::format(JO_Session::get('total'));
		$this->view->percent = Model_Percentes::getPercentRow($user);
		
		$help = Model_Pages::get(JO_Registry::forceGet('page_upload_item'));
        if($help) {
        	$this->view->page_upload_item = array(
        		'name' => $help['name'],
        		'href' => WM_Router::create($request->getBaseUrl() . '?controller=pages&page_id='. $help['id'] .'&name='.WM_Router::clearName($help['name']))
        	);
        }
		
		$this->view->file_upload = WM_Router::create($request->getBaseUrl() . '?module=themes&controller=upload&action=doupload');
		
		$this->view->children = array();
    	$this->view->children['header_part'] = 'layout/header_part';
    	$this->view->children['footer_part'] = 'layout/footer_part';   	
	}
	
	/* RETURN SUBDIRS */
	public function get_categoriesAction() {
		$this->noLayout(true);
		
		$request = $this->getRequest();
	    
		$category_id = $request->getRequest('get_categories');
		$result = '';
		
		if($category_id) {
			$allCategories = Model_Categories::getWithChilds();
			
			$categoriesSelect = Model_Categories::generateSelect($allCategories, $category_id, $category_id);
			
			if($categoriesSelect) {
		    	$categories = explode('|', $categoriesSelect);
				foreach($categories as $category) {
					$c = explode('>', $category);
					if(!empty($c[0]))
						$result .= '<p><input type="checkbox" name="category['. $c[0] .']" id="custom_categories_'. $c[0] .'" /><label for="custom_categories_'. $c[0] .'">'. $c[1] .'</label></p>';
				}
			}
			
			die($result);
		}
	}
	
	/* UPLOAD ZIP */
	public function douploadAction() {
		$this->noViewRenderer(true);
		
		$request = $this->getRequest();
	    
	    if(!JO_Session::get('user_id')) {
	    	JO_Session::set('msg_error', $this->translate('You must be logged to upload an item'));
	    	die(json_encode(array('logout' => WM_Router::create($request->getBaseUrl() .'?controller=users&action=login'))));
	    }

		$file = $request->getFile('file');
		
		$result = array();
		   
        if(!$file) {
        	$result['msg_error'] = $this->translate('Invalid upload');
			die(json_encode($result));
        }
		
		$upload_folder  = realpath(BASE_PATH . '/uploads');
		$upload_folder .= '/temporary/' . JO_Date::getInstance(JO_Session::get('register_datetime'), 'yy/mm', true) . '/';
		
		$upload = new JO_Upload;
		$types = array();
		$fileTypes = JO_Registry::get('upload_theme');
	    
	    if($fileTypes) {
	    	foreach($fileTypes as $type) {
	        	$tp = explode(',', $type);
	        	foreach($tp as $t) {
	        		$types[] = '.'.$t;
				}
	        }
	    }
     	
		$allow_images = array();
		if(isset($fileTypes['images'])) {
			$ew = explode(',', $fileTypes['images']);
			foreach($ew AS $ar) {
				$allow_images[] = '.'.strtolower($ar);
			}
		}
		
		$upload->setFile($file)
				->setExtension($types)
				->setUploadDir($upload_folder);
		
		$new_name = md5(time() . serialize($file)); 
        
		if($upload->upload($new_name)) {
		    $file_extension = $upload->get_extension($file['name']);
			$info = $upload->getFileInfo();
			
			if($file_extension == '.zip') {
				if($info) {
					$zip = new ZipArchive;
					$fileArr = array();
					
	        		$res = $zip->open($upload_folder . $upload->getNewFileName());
					if($res === true) {
						
						for($i = 0; $i < $zip->numFiles; $i++) {
							$zip_file =  $zip->statIndex($i);
							if(in_array($upload->get_extension($zip_file['name']), $allow_images)) {
								if( stripos($zip_file['name'], '_MACOSX') !== false ) { continue; }
								$ext = $upload->get_extension($zip_file['name']);
								$name = basename($zip_file['name'], $ext);
								
								$fileArr[] = array(
									'zip_filename' => $info['name'],
									'zip_name' => $file['name'],
				        			'filename' => md5($name) . $ext,
				        			'name' => $name . $ext,
				        			'size' => number_format($zip_file['size'], 2),
				        			'uploaded' => time()
				        		);
						    }
						}
						$zip->close();
						
						if(!empty($fileArr)) {
							if(JO_Session::get('uploaded_files')) {
			        		    $array = JO_Session::get('uploaded_files');
			        		} else {
			        			 $array = array();
							}	
							
							$array[] = $fileArr;
							
		     		    	JO_Session::set('uploaded_files', $array);
							
						} else {
							$fileArr[] = array(
								'zip_filename' => $info['name'],
								'zip_name' => $file['name'],
								'filename' => '',
			        			'name' => '',
			        			'size' => '',
			        			'uploaded' => time()
							);
						}
						
						if(JO_Session::get('uploaded_arhives')) {
		        		    $array = JO_Session::get('uploaded_arhives');
		        		} else {
		        			 $array = array();
						}
						
		        		$array[] = array(
		        			array(
								'filename' => $info['name'],
		        				'name' => $file['name']
		        			)
						);
						
		     		    JO_Session::set('uploaded_arhives', $array);
						
					} else {
						$result['msg_error'] = $this->translate('Theme preview should be '.implode(', ', $allow_archives).' file');
					}
				} else {
					$result['msg_error'] = $this->translate('Invalid upload');
				}
			} else {
				if($info) {
					$fileArr[] = array(
						'zip_filename' => '',
						'zip_name' => '',
	        			'filename' => $info['name'],
	        			'name' => $file['name'],
	        			'size' => number_format($info['size'] / 1024 / 1024, 2),
	        			'uploaded' => time()
	        		);
					
	        		if(JO_Session::get('uploaded_files')) {
	        		    $array = JO_Session::get('uploaded_files');
	        		} else {
	        			 $array = array();
					}
					
	        		$array[] = $fileArr;
	     		    JO_Session::set('uploaded_files', $array); 
				} else {
					$result['msg_error'] = $this->translate('Invalid upload');
				}
			}
		}
            
        if(is_array($fileArr)) {
        	$result['msg_success'] = $this->translate('File was uploaded successful');
			$result['file'] = $fileArr;
			
        	die(json_encode($result));
        } else {
        	$result['msg_error'] = $this->translate('Invalid upload');
			
        	die(json_encode($result));
        }
	}
	
	/* UPLOAD FORM */
	public function uploadAction() {
		$this->noViewRenderer(true);
		$request = $this->getRequest();
		
		if($request->isPost()) {
			$error = array(); 
            $base_upload_folder  = realpath(BASE_PATH . '/uploads');
			$temp_upload_folder = $base_upload_folder .'/temporary/'. JO_Date::getInstance(JO_Session::get('register_datetime'), 'yy/mm', true) . '/';
			  
			$fileTypes = JO_Registry::get('upload_theme');
			
			if(isset($fileTypes['archives'])) {
    			$ew = explode(',', $fileTypes['archives']);
    			foreach($ew AS $ar) {
    				$allow_archives[] = '.'.strtolower($ar);
    			}
    		}
			
			$allow_images = array();
			if(isset($fileTypes['images'])) {
				$ew = explode(',', $fileTypes['images']);
				foreach($ew AS $ar) {
					$allow_images[] = '.'.strtolower($ar);
				}
			}
			
            if(trim($request->getPost('name')) == '') {
    			$error['ename'] = $this->translate('You have to input a name');
    		}
        
    		if(trim($request->getPost('description')) == '') {
    			$error['edescription'] = $this->translate('You have to input a description');
    		}
        	
    		if(trim($request->getPost('theme_preview')) == '') {
    			$error['etheme_preview'] = $this->translate('You have to choose a theme preview');
    		}
        	
			if(trim($request->getPost('theme_preview_zip')) == '') {
        		$error['etheme_preview_zip'] = $this->translate('You have to choose a file');
        	} else {
    			if(!in_array(strtolower(strrchr($request->getPost('theme_preview_zip'), '.')), $allow_archives)) {
    				$error['etheme_preview_zip'] = $this->translate('Preview archive file should be '.implode(', ',$allow_archives).' file');
    			} elseif(!file_exists($temp_upload_folder . $request->getPost('theme_preview_zip'))) {
    				$error['etheme_preview_zip'] = $this->translate('Preview archive file should be '.implode(', ',$allow_archives).' file');
    			}
    		}
			
        	if(trim($request->getPost('main_file')) == '') {
        		$error['emain_file'] = $this->translate('You have to choose a file');
        	} else {
    			if(!in_array(strtolower(strrchr($request->getPost('main_file'), '.')), $allow_archives)) {
    				$error['emain_file'] = $this->translate('Main file should be '.implode(', ',$allow_archives).' file');
    			} elseif(!file_exists($temp_upload_folder . $request->getPost('main_file'))) {
    				$error['emain_file'] = $this->translate('Main file should be '.implode(', ',$allow_archives).' file');
    			}
    		}
        
    		if(!$request->getPost('category')) {
    			$error['ecategory'] = $this->translate('You have to choose a category');
    		} elseif(!is_array($request->getPost('category'))) {
    			$error['ecategory'] = $this->translate('You have to choose a category');
    		} elseif(!count($request->getPost('category'))) {
    			$error['ecategory'] = $this->translate('You have to choose a category');
    		}
        	
			$attributes = Model_Attributes::getAllWithCategories("attributes_categories.categories LIKE '%,".(int) $request->getPost('category_id') .",%'");	
    		
    		if(is_array($attributes)) {
    			$attributesError = false;
				$cnt = count($attributes);
    			for($i = 0; $i < $cnt; $i++) {			

    				if(!$request->getPost('attributes['.$attributes[$i]['head_id'].']') && $attributes[$i]['required']) {
    					$attributesError = true;
    					break;
    				}				
    			}
    			
    			if($attributesError) {
    				$error['eattributes'] = $this->translate('You have to mark all the attributes');
    			}
    		}
        		
    		if(trim($request->getPost('tags')) == '') {
    			$error['etags'] = $this->translate('You have to fill the field with tags');
    		}
        		
    		if(!$request->getPost('source_license')) {
    			$error['esource_license'] = $this->translate('You have to confirm that you have rights to use all the materials in your template');
    		}
        		
    		if($request->getPost('demo_url') && filter_var($request->getPost('demo_url'), FILTER_VALIDATE_URL) === false) {
    			$error['edemo_url'] = $this->translate('Please enter valid url for demo preview');
    		}
        	
    		if(!$request->getPost('suggested_price') || !preg_match('#^\d+(?:\.\d{1,})?$#', $request->getPost('suggested_price'))) {
    			$error['esuggested_price'] = $this->translate('Suggested price should be in the format: number(.number)');
    		}       		
			
    		if(count($error) > 0) {
				$error['msg_error'] = $this->translate('Upload error');
    		    JO_Session::set('msg_error', $error);
				JO_Session::set('data', $request->getParams());
				$this->redirect(WM_Router::create($request->getBaseUrl() . '?controller=upload&action=form&category_id='. $request->getPost('category_id')));
    		} else {
	
        	    $free_request = $request->getPost('free_request') ? 'true' : 'false';

    		    $id = Model_Items::add(array(
    		        'user_id'	        =>    JO_Session::get('user_id'),
        	        'name'			    =>    $request->getPost('name'),
        	        'description'	    =>    $request->getPost('description'),
        	        'demo_url'	        =>    $request->getPost('demo_url'),
        	        'reviewer_comment'	=>    $request->getPost('reviewer_comment'),
        	        'suggested_price'	=>    $request->getPost('suggested_price'),
        	        'free_request'	    =>    $free_request,
    		    	'default_module'	=> 	  'themes'
    		    ));
				
        		$upload_folder = $base_upload_folder. '/items/'. date("Y/m/") . $id.'/';
				if(!is_dir($upload_folder)) {
					mkdir($upload_folder, 0777, true);
				}

				$theme_preview = $request->getPost('theme_preview');
				$zip_file = $request->getPost('theme_preview_zip');
				$main_file = $request->getPost('main_file');
				
				copy($temp_upload_folder .$zip_file, $upload_folder . $zip_file);
				copy($temp_upload_folder .$main_file, $upload_folder . $main_file);
				
				$uploaded_files = JO_Session::get('uploaded_files');
				$upload_file = array();
				foreach($uploaded_files[0] as $f) {
					if($f['filename'] == $theme_preview) {
						$upload_file = $f;
						break;
					}
				}
				
				if(!is_dir($upload_folder .'preview/')) {
					mkdir($upload_folder .'preview/', 0777, true);
				}
				
				$found = false;
				
				if(file_exists($temp_upload_folder . $theme_preview)) {
					copy($temp_upload_folder . $theme_preview, $upload_folder . $theme_preview);
					$preview = $theme_preview;
					$found = true;
				}
			
				$zip = new ZipArchive;
				
				$res = $zip->open($upload_folder . $zip_file);
				if($res == true) {
					for($i = 0; $i < $zip->numFiles; $i++) {
						$file = $zip->getNameIndex($i);
						if( stripos($file, '_MACOSX') !== false ) { continue; }
						if(in_array(strtolower(strrchr($file, '.')), $allow_images)) {
							$fileinfo = pathinfo($file);
							
							$prw_filename = $this->rename_if_exists($upload_folder .'preview/', $fileinfo['basename']);
							copy("zip://" . $upload_folder . $zip_file ."#". $file, $upload_folder .'preview/'. $prw_filename);
							
							if(!$found && !empty($fileinfo['basename']) && $fileinfo['basename'] == $upload_file['name']) {
							
								$filename = $this->rename_if_exists($upload_folder, $fileinfo['basename']);
								$found = true;
								if(copy("zip://" . $upload_folder . $zip_file ."#". $file, $upload_folder . $filename)) {
									$preview = $filename;
								}
							}
						}
					}
				
					$zip->close();
				}
				
				if(!$found) {
					$res = $zip->open($upload_folder . $main_file);
					
					for($i = 0; $i < $zip->numFiles; $i++) {
						$file = $zip->getNameIndex($i);
						if( stripos($file, '_MACOSX') !== false ) { continue; }
						if(in_array(strtolower(strrchr($file, '.')), $allow_images)) {
							$fileinfo = pathinfo($file);
							
							if(!empty($fileinfo['basename']) && $fileinfo['basename'] == $upload_file['name']) {
								$filename = $this->rename_if_exists($upload_folder, $fileinfo['basename']);
								
								if(copy("zip://" . $upload_folder . $main_file ."#". $file, $upload_folder . $filename)) {
									$preview = $upload_folder . $filename;
								}
							}
						}
					}
				
					$zip->close();
				}
        		
            	$item_folder = 	str_replace( $base_upload_folder, '', $upload_folder);
				$uploaded_arhives = JO_Session::get('uploaded_arhives');
				
				$upload_zip = array();
				foreach($uploaded_arhives[0] as $f) {
					
					if($f['filename'] == $main_file) {
						$upload_zip = $f;
						break;
					}
				}
				
				Model_Items::updatePics(array(
        		    'id'	=>    $id,
        		    'thumbnail'	=>  $item_folder . $preview,
        		    'theme_preview_thumbnail'	=>   $item_folder . $preview,
        		    'theme_preview'	=>    $item_folder . $zip_file,
        		    'main_file'		=>    $item_folder . $main_file,
        		    'main_file_name'	=>   $upload_zip['name'] 
        		));
        		
				$cats = $request->getPost('category');
			
				Model_Categories::addToItem($id, $cats, $request->getPost('category_id'));
				
				$str_tags = trim($request->getPost('tags'), ',');
    			$arr = explode(',', $str_tags);
				Model_Tags::addToItem($id, $arr);
        		
				if($request->getPost('attributes')) {
        			Model_Attributes::addToItem($id, $request->getPost('attributes'));
				}
				
				if($uploaded_files) {
        			foreach($uploaded_files[0] as $f) {
        				if(file_exists($temp_upload_folder . $f['filename']))
        					unlink($temp_upload_folder . $f['filename']);
        			}
        		}	
        		JO_Session::clear('uploaded_files');	
	           	
				if($uploaded_arhives) {
        			foreach($uploaded_arhives[0] as $f) {
        				
        				if(file_exists($temp_upload_folder . $f['filename']))
        					unlink($temp_upload_folder . $f['filename']);
        			}
        		}	
        		JO_Session::clear('uploaded_arhives');
				
				$category_info = Model_Categories::get($request->getPost('category_id'));
				
        		$is_mail_smtp = JO_Registry::forceGet('config_mail_smtp');
				$not_template = Model_Notification::getNotification('item_added');			
				$mail = new JO_Mail;
				if($is_mail_smtp) {
					$mail->setSMTPParams(JO_Registry::forceGet('config_mail_smtp_host'), JO_Registry::forceGet('config_mail_smtp_port'), JO_Registry::forceGet('config_mail_smtp_user'), JO_Registry::forceGet('config_mail_smtp_password'));
				}
				$domain = $request->getDomain();
				$mail->setFrom('no-reply@'.$domain);
				$mail->setReturnPath('no-reply@'.$domain);
				$mail->setSubject($this->translate('New item for approval') . ' ' . JO_Registry::get('store_meta_title'));
                if($not_template) {
        			$title = $not_template['title'];
        			$html = html_entity_decode($not_template['template'], ENT_QUOTES, 'utf-8');
        			$html = str_replace('{URL}', $request->getBaseUrl().'/admin/queueitems/edit/?m='.$category_info['module'].'&id='.$id , $html);
                } else {
    				$html = nl2br('Hello,

					There is a new item waiting for approval. You can see it on '. $request->getBaseUrl().'/admin/queueitems/edit/?m='. $category_info['module'] .'&id='. $id .'');
                }
		        $mail->setHTML($html);
				
				$result = (int)$mail->send(array(JO_Registry::get('report_mail')), ($is_mail_smtp ? 'smtp' : 'mail'));
        		
        		
        		
        		JO_Session::set('msg_success', $this->translate('Your item has been added successfully!'));
	            $this->redirect(WM_Router::create($request->getBaseUrl() . '?controller=users&action=dashboard'));
        	}
		}

		$this->redirect(WM_Router::create($request->getBaseUrl() . '?controller=upload'));
	}
	
    private function scandir($dir) {
		$dir = rtrim($dir, '/');
		$data = scandir($dir);
		$files = array();
		if($data) {
			foreach($data AS $d) {
				if(!in_array($d, array('.','..'))) {
					if(is_dir($dir . '/' . $d)) {
						$res = self::scandir($dir . '/' . $d);
						$files = array_merge($files, $res);
					} else {
						$files[] = $dir . '/' . $d;
					}
				}
			}
		}
		return $files;
	}
	
	private function rename_if_exists($dir, $filename) {
    	$dir = rtrim($dir, '/');
	    $ext = strrchr($filename, '.');
	    $prefix = substr($filename, 0, -strlen($ext));
	    $i = 0;
	    while(file_exists($dir . $filename)) { // If file exists, add a number to it.
	        $filename = $prefix . '[' .++$i . ']' . $ext;
	    }
	    return $filename;
	}
	
}

?>