<?php

class Helper_Images {

	private $dirImages;
	
	private $httpImages;
	
	public function __construct() {
				
		$this->dirImages = realpath(BASE_PATH . '/' . 'uploads') . '/';
		
		if(!$this->dirImages || !file_exists($this->dirImages) || !is_dir($this->dirImages)) {
			throw new JO_Exception('Upload folder not exist!');
		}

		$this->httpImages = 'uploads/';
		
	}
	
	public function resize($filename, $width, $height, $crop = false, $watermark = false, $gray = false) {
		if(!$width && !$height) { $width = 1;$height = 1; }
		if (!file_exists($this->dirImages . $filename) || !is_file($this->dirImages . $filename)) {
			$filename = JO_Registry::forceGet('no_image');
			if (!file_exists($this->dirImages . $filename) || !is_file($this->dirImages . $filename)) {
				$filename = '/no_image.jpg';
				if (!file_exists($this->dirImages . $filename) || !is_file($this->dirImages . $filename)) {
					return;	
				}
			}
		} 

		$info = pathinfo($filename);
		$extension = $info['extension'];
		$gray_name = '';
		if($gray) {
			$gray_name = '_gray';	
		}
		if($crop) {
			$gray_name .= '_crop';	
		}
		if($watermark && JO_Registry::get($watermark) && file_exists(BASE_PATH . '/uploads' . JO_Registry::get($watermark))) {
			$gray_name .= '_watermark';	
		}
		
		$old_image = $filename;
		
		$tmp = substr($filename, 0, strrpos($filename, '.'));
		$filename = substr($tmp, 0, strrpos($tmp, '/')) . '/' . md5(basename($tmp)) . '-' . md5($filename);

		$new_image = 'cache' . $filename . '-' . $width . 'x' . $height . $gray_name . '.' . $extension;
		$new_image = str_replace('/../','/',$new_image);
		if (!file_exists($this->dirImages . $new_image) || (filemtime($this->dirImages . $old_image) > filemtime($this->dirImages . $new_image))) {
			$path = '';
			$directories = explode('/', dirname(str_replace('../', '', $new_image)));
			
			foreach ($directories as $directory) {
				$path = $path . '/' . $directory;
				
				if (!file_exists($this->dirImages . $path)) {
					@mkdir($this->dirImages . $path, 0777, true);
				}		
			}
			
			
			$image = new JO_GDThumb($this->dirImages . $old_image);
			
			if($crop === false) {
				$image->resize($width, $height);
			} else {
				$image->resize_crop($width, $height);
			} 
			
			if($watermark && JO_Registry::get($watermark) && file_exists(BASE_PATH . '/uploads/' . JO_Registry::get($watermark))) {
				$image->watermark(BASE_PATH . '/uploads/' . JO_Registry::get($watermark), false);
			}
			
			$image->save($this->dirImages . $new_image, $gray);
		}
		
		return $this->httpImages . $new_image;
	}
	
	public function resizeWidth($filename, $width, $watermark = false) {
		if ($file = fopen($this->dirImages . $filename,'r') == false || !is_file($this->dirImages . $filename)) {
			return;
		} 
		
		$imag_info = @getimagesize($this->dirImages . $filename);
		
		if(!$imag_info) {
			return;
		}
		
		if($imag_info[0]/$width < 1) {
			return $this->resize($filename, $imag_info[0], $imag_info[1], false, $watermark);
		}
		
		$new_height = round($imag_info[1] / ($imag_info[0]/$width));
		
		return $this->resize($filename, $width, $new_height, false, $watermark);
		
	}
	
	public function resizeHeight($filename, $height, $watermark = false) {
		if (!file_exists($this->dirImages . $filename) || !is_file($this->dirImages . $filename)) {
			return;
		} 
		
		$imag_info = getimagesize($this->dirImages . $filename);
		
		if(!$imag_info) {
			return;
		}
		
		if($imag_info[1]/$height < 1) {
			return $this->resize($filename, $imag_info[0], $imag_info[1], false, $watermark);
		}
		
		$new_width = round($imag_info[0] / ($imag_info[1]/$height));
		
		return $this->resize($filename, $new_width, $height, false, $watermark);
		
	}
	
	public function deleteImages($file, $delete_real = true) { 
		
		if(file_exists($this->dirImages . $file) && is_file($this->dirImages . $file)) { 
			$ext = explode('.',$file);
			$ext = '.' . end($ext);
			$filem = str_replace($ext, '', $file);
			
			$files = glob($this->dirImages . 'cache' . '/' . $filem . '*' . $ext);
			if(is_array($files)) {
				foreach($files AS $file_delete) {
					if(is_file($file_delete)) {
						@unlink($file_delete);
					}
				}
			}
			
			$tmp = substr($file, 0, strrpos($file, '.'));
			$filename = substr($tmp, 0, strrpos($tmp, '/')) . '/' . md5(basename($tmp)) . '-' . md5($file);
		
			$files = glob($this->dirImages . 'cache' . '/' . $filename . '*' . $ext);
			if(is_array($files)) {
				foreach($files AS $file_delete) {
					if(is_file($file_delete)) {
						@unlink($file_delete);
					}
				}
			}
			
			if($delete_real) {
				@unlink($this->dirImages . $file);
			}
		}
	}
	
	
	public function fixEditorText($text) {
		
		$dom = new JO_Html_Dom();
		$dom->load($text);
		$tags = $dom->find('img[src^=uploads/]');
		$orig = $repl = array();
		foreach($tags AS $tag) {
			$src = $tag->src;
			$width = $tag->width;
			$height = $tag->height;
			$style = $tag->style;
			if(!$width && preg_match('/width:\s?([\d]{1,})/i',$style, $css)) {
				$width = $css[1];
			}
			if(!$height && preg_match('/height:\s?([\d]{1,})/i',$style, $css)) {
				$height = $css[1];
			}
			
			if($width || $height) {
				$generate = self::resizeForEditor($src, $width, $height);
				if($generate) {
					$orig[] = '/src=[\"\'\s]?'.self::_preg_quote($src, "/").'[\"\'\s]?/i';
					$repl[] = 'src="'.$generate.'"';
				}
			}	
		}
		
		if (count($orig)) {
			$text = preg_replace($orig, $repl, $text);
		}
		
		return $text;
	}
	
	private function resizeForEditor($filename, $width = false, $height = false) {
		$filename = preg_replace('/uploads\//i','/',$filename);
		if (!file_exists($this->dirImages . $filename) || !is_file($this->dirImages . $filename)) {
			return false;
		} 
		
		$imag_info = @getimagesize($this->dirImages . $filename);
		if(!$imag_info) {
			return;
		}
		
		$info = pathinfo($filename);
		$extension = $info['extension'];
		
		if($height && !$width) {
			$width = round($imag_info[0] / ($imag_info[1]/$height));
		} elseif($width && !$height) {
			$height = round($imag_info[1] / ($imag_info[0]/$width));
		}
		if(!$width || !$height) {
			return false;
		}
		
		$old_image = $filename;
		$new_image = 'cache' . substr($filename, 0, strrpos($filename, '.')) . '-' . $width . 'x' . $height . '.' . $extension;
		
		if (!file_exists($this->dirImages . $new_image) || (filemtime($this->dirImages . $old_image) > filemtime($this->dirImages . $new_image))) {
			$path = '';
			$directories = explode('/', dirname(str_replace('../', '', $new_image)));
			
			foreach ($directories as $directory) {
				$path = $path . '/' . $directory;
				
				if (!file_exists($this->dirImages . $path)) {
					@mkdir($this->dirImages . $path, 0777, true);
				}		
			}
			
			$image = new JO_GDThumb($this->dirImages . $old_image);
			$image->resize($width, $height, false);
			$image->save($this->dirImages . $new_image);
		}
		
		return $this->httpImages . $new_image;
		
	}
	
	private static function _preg_quote($str, $delimiter) {
		$text = preg_quote($str);
		$text = str_replace($delimiter, '\\' . $delimiter, $text); 
		return $text;
	}	
	
}

?>