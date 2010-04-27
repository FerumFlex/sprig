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
	
	public function __construct(array $options = NULL)
	{
		if ( ! empty($options['path']))
			$options['path'] = Kohana::config('upload.directory').$options['path'];
		
		if (empty($options['path']) OR ! (is_dir($options['path']) OR mkdir($options['path'], 0777, TRUE)))
		{
			throw new Sprig_Exception('File fields must have a directory path to save and load files from');
		}

		parent::__construct($options);

		// Make sure the path has a trailing slash
		$this->path = rtrim(str_replace('\\', '/', $this->path), '/').'/';
		
		// Handle uploads
		$this->callbacks[] = array($this, '_check_empty');
		$this->callbacks[] = array($this, '_upload');
	}

	public function input($name, $value, array $attr = NULL)
	{
		$delete = $name.'_delete';
		
		$text = Form::file($name, $attr);
		if ($value)
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
		return $this->path.$value;
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

		if (Upload::valid($file) AND  Upload::type($file, $this->types))
		{
			$this->delete($this->object->original($input));
			
			$array[$input] = basename(Upload::save($file, NULL, $this->path));
			
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
			$file = $this->verbose($value);
			if (file_exists($file))
				unlink($file);
		}
	}
}