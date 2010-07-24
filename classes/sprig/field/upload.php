<?php defined('SYSPATH') or die('No direct script access.');
/**
 * @package		AltConstructor
 * @author		Anton <anton@altsolution.net>
 */
class Sprig_Field_Upload extends Sprig_Field_Char {
	
	/**
	 * @var  string  path where the file will be saved to/ loaded from
	 */
	public $path;

	/**
	 * @var  array   types of images to accept
	 */
	public $types = array('jpg', 'jpeg', 'png', 'gif');
	
	/*
	 * base dir for uploads
	*/
	public $base_dir = NULL;
	
	public $url = NULL;
	
	public function __construct(array $options = NULL)
	{
		if (empty($options['base_dir']))
			$options['base_dir'] = Kohana::config('upload.directory');
		
		if (empty($options['url']))
			$options['url'] = Kohana::config('upload.url');
		
		// Normalize the directory path
		$options['base_dir'] = rtrim(str_replace(array('\\', '/'), '/', $options['base_dir']), '/').'/';
		
		$options['path'] = rtrim(str_replace(array('\\', '/'), '/', $options['path']), '/').'/';

		if ( ! (is_dir($options['base_dir'].$options['path']) OR mkdir($options['base_dir'].$options['path'], 0777, TRUE)))
		{
			throw new Sprig_Exception('Upload fields must define a directory path');
		}

		parent::__construct($options);

		// Handle uploads
		$this->callbacks[] = array($this, '_check_empty');
		$this->callbacks[] = array($this, '_upload');
	}

	public function input($name, $value, array $attr = NULL)
	{
		$delete = $name.'_delete';
		
		$text = Form::file($name, $attr);
		if ($this->object->original($this->column))
		{
			if ( ! is_array($value))
				$text.= '<br />'.HTML::anchor($this->verbose($value));
			if ($this->empty == TRUE)
				$text .= '<br />'.Form::checkbox($delete, '1', FALSE, array('id'=>$delete)).Form::label($delete, 'Удалить');
		}
		
		return $text;
	}

	public function verbose($value)
	{
		return $this->url.$this->path.$value;
	}

	public function _check_empty(Validate $array, $input)
	{
		// Get the file from the array
		$file = $array[$input];
		
		if ( ! $this->empty AND ! $this->object->loaded())
		{
			if ( ! Upload::valid($file) OR ! Upload::not_empty($file))
				$array->error($input, 'not_empty');
		}
	}
	
	public function _upload(Validate $array, $input)
	{
		if ($array->errors())
		{
			// Don't bother uploading
			return;
		}

		// Get the file from the array
		$file = $array[$input];
		
		if ( ! Upload::valid($file) OR ! Upload::not_empty($file))
		{
			if (isset($_POST[$this->column.'_delete']))
			{
				$this->delete($this->object->original($input));
				$array[$input] = '';
			} else
				unset($array[$input]);
			// No need to do anything right now
			return;
		}

		if (Upload::valid($file))
		{
			if ($this->types AND ! Upload::type($file, $this->types))
			{
				$array->error('file', 'valid');
			} else {
				$this->delete($this->object->original($input));
				$filename = uniqid().filter::filename(pathinfo($file['name'], PATHINFO_BASENAME));
				$dir = $this->base_dir.$this->path;
				is_dir($dir) OR mkdir($dir);
				
				$array[$input] = basename(Upload::save($file, $filename, $this->base_dir.$this->path));
			}
		}
		else
		{
			$array->error('file', 'valid');
		}
	}
	
	public function delete($value)
	{
		if ($value)
		{
			$file = $this->file($value);
			if (file_exists($file))
				unlink($file);
		}
	}
	
	public function file($value)
	{
		return $this->base_dir.$this->path.$value;
	}
}