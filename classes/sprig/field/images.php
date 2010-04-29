<?php defined('SYSPATH') or die('No direct script access.');
/**
 * @package		AltConstructor
 * @author		Anton <anton@altsolution.net>
 */
class Sprig_Field_Images extends Sprig_Field_Image {
	
	public $images = array(
		'default' => array(
			'func' => 'Sprig_Copy::copy',
		)
	);
	
	public function _upload_image(Validate $array, $input)
	{
		if ($array->errors())
		{
			// Don't bother uploading
			return;
		}
		
		// Get the image from the array
		$image = $array[$input];
		
		if ( ! Upload::valid($image) OR ! Upload::not_empty($image))
		{
			if (isset($_POST[$this->column.'_delete']))
			{
				$this->delete();
				$array[$input] = '';
			} else
				unset($array[$input]);
			// No need to do anything right now
			return;
		}

		if (Upload::valid($image) AND  Upload::type($image, $this->types))
		{
			$this->delete($this->object->original($input));
			
			if ($tmp_file = Upload::save($image, NULL, $this->base_dir.$this->tmp_dir))
			{
				$files = array();
				foreach ($this->images as $type=>$data)
				{
					$params = arr::get($data, 'params', array());
					$dir = $this->base_dir.$this->directory;
					
					is_dir($dir) OR mkdir($dir);
					$file = call_user_func($data['func'], $tmp_file, $dir, $this->rand($tmp_file), $params);
					if (empty($file))
					{
						$array->error('image', 'failed');
						return;
					}
					
					$files[$type] = $file;
				}
				
				// Delete the temporary file
				unlink($tmp_file);

				// Update the image filename
				$array[$input] = serialize($files);
			}
			else
			{
				$array->error('image', 'failed');
			}
		}
		else
		{
			$array->error('image', 'valid');
		}
	}
	
	public function verbose($value, $type = 'default')
	{
		$value = $this->to_array($value);
		return (isset($value[$type]) ? $this->url.$this->directory.$value[$type] : $this->empty_image);
	}
	
	public function to_array($value)
	{
		if (is_string($value))
			$value = unserialize($value);
		
		return $value;
	}
	
	public function delete($value)
	{
		$value = $this->to_array($value);
		
		if (is_array($value))
		{
			foreach ($value as $type=>$default)
			{
				$file = $this->base_dir.$this->directory.$default;
				if (file_exists($file))
					unlink($file);
			}
		}
	}
}