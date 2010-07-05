<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Sprig multiple-choice (enum) field.
 *
 * @package    Sprig
 * @author     Woody Gilk
 * @copyright  (c) 2009 Woody Gilk
 * @license    MIT
 */
class Sprig_Field_Multienum extends Sprig_Field_Enum {

	public function verbose($value)
	{
		$value = unserialize($value);
		
		$array = array();
		if (is_array($value))
		{
			foreach ($value as $key=>$value)
			{
				if (isset($this->choices[$key]))
					$array[] = $this->choices[$key];
			}
		}
		
		return implode('<br />', $array);
	}
	
	public function to_array($value)
	{
		$array = unserialize($value);
		
		$value = array();
		if (is_array($array))
		{
			foreach ($array as $key)
			{
				if (isset($this->choices[$key]))
					$value[] = $this->choices[$key];
			}
		}
		
		return $array;
	}

	public function value($value)
	{
		if (is_array($value))
			$value = serialize($value);
		
		return $value;
	}
	
	public function input($name, $value, array $attr = NULL)
	{
		if (is_string($value))
			$value = unserialize($value);
		
		$inputs = array();
		foreach ($this->choices as $key => $label)
		{
			$inputs[] = '<label>'.Form::checkbox("{$name}[]", $key, in_array((string)$key, $value)).' '.$label.'</label>';
		}

		// Hidden input is added to force $_POST to contain a value for
		// this field, even when nothing is selected.

		return Form::hidden($name, '').implode('<br/>', $inputs);
	}
} // End Sprig_Field_Multienum