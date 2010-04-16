<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Sprig image field.
 *
 * @package    Sprig
 * @author     Woody Gilk
 * @author     Kelvin Luck
 * @copyright  (c) 2009 Woody Gilk
 * @license    MIT
 */
class Sprig_Field_Image extends Sprig_Field_Char {

	/**
	 * @var  integer  image width
	 */
	public $width;

	/**
	 * @var  integer  image height
	 */
	public $height;

	/**
	 * @var  string  directory where the image will be loaded from
	 */
	public $directory;

	/**
	 * @var  integer  one of the Image resize constants
	 */
	public $resize = Image::AUTO;

	/**
	 * @var  array   types of images to accept
	 */
	public $types = array('jpg', 'jpeg', 'png', 'gif');
	
	/**
	 * @var  func   function for upload images
	*/
	public $save_image = array(
		'func' => 'Sprig_Copy::copy',
	);
	
	/*
	 * @var string show this image on empty
	*/
	public $empty_image;

	/*
	 * tmp dir
	*/
	public $tmp_dir = 'upload/tmp';
	
	public function __construct(array $options = NULL)
	{
		if (empty($options['directory']) OR ! (is_dir($options['directory']) OR mkdir($options['directory'], 0777, TRUE)))
		{
			throw new Sprig_Exception('Image fields must define a directory path');
		}

		// Normalize the directory path
		$options['directory'] = rtrim(str_replace(array('\\', '/'), '/', $options['directory']), '/').'/';

		parent::__construct($options);

		if (empty($this->tmp_dir) OR ! (is_dir($this->tmp_dir) OR mkdir($this->tmp_dir, 0777, TRUE)))
		{
			throw new Sprig_Exception('Image fields must define a tmp directory');
		}
		// Handle uploads
		$this->callbacks[] = array($this, '_upload_image');
	}

	public function input($name, $value, array $attr = NULL)
	{
		$delete = $name.'_delete';
		
		$text = '';
		if ($value AND ! is_array($value))
			$text .= Html::image($this->verbose($value)).'<br />';
		
		$text .= Form::file($name, $attr);
		if ($value AND $this->empty == TRUE)
			$text .= '<br />'.Form::checkbox($delete, '1', FALSE, array('id'=>$delete)).Form::label($delete, 'Удалить');
		
		return $text;
	}

	public function verbose($value)
	{
		return ($value ? $this->directory.$value : $this->empty_image);
	}

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
				$this->delete($this->object->original($input));
				$array[$input] = '';
			} else
				unset($array[$input]);
			// No need to do anything right now
			return;
		}

		if (Upload::valid($image) AND  Upload::type($image, $this->types))
		{
			$this->delete($this->object->original($input));

			if ($file = Upload::save($image, NULL, $this->tmp_dir))
			{
				$filename = $this->rand($file);
				
				$params = arr::get($this->save_image, 'params', array());
				$array[$input] = call_user_func($this->save_image['func'], $file, $this->directory, $filename, $params);
				
				// Delete the temporary file
				unlink($file);
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
	
	public function delete($value)
	{
		if ($value)
		{
			$file = $this->verbose($value);
			if (file_exists($file))
				unlink($file);
		}
	}

	public function rand($file)
	{
		return (string)time().Text::random('alnum', 20).'.'.pathinfo($file, PATHINFO_EXTENSION);
	}
} // End Sprig_Field_Image
