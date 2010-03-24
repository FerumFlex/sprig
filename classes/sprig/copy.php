<?php defined('SYSPATH') or die('No direct script access.');
/**
 * @package		AltConstructor
 * @author		Anton <anton@altsolution.net>
 */
class Sprig_Copy {
	
	public static function copy($src, $dir, $dest)
	{
		copy($src, $dir.$dest);
		return $dest;
	}
	
	public static function crop($src, $dir, $dest, $params)
	{
		$width = arr::get($params, 'width', '');
		$height = arr::get($params, 'height', '');
		$image = Image::factory($src);
		
		$width_ratio = $width / $image->width;
		$height_ratio = $height / $image->height;
		
		if (($width_ratio < 1) AND ($height_ratio < 1))
		{
			$ratio = $width_ratio > $height_ratio ? $width_ratio : $height_ratio;
			$image->resize ($image->width * $ratio, $image->height * $ratio);
		}
		$image->crop($width, $height)->save($dir.$dest);
		
		return $dest;
	}
}