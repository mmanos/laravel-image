<?php namespace Mmanos\Image;

use Exception;

/**
 * Image Resizer.
 */
class Resizer
{
	/** Scale image to exact height and width. */
	const SCALE_EXACT = 'exact';
	
	/** Scale image to exact height contraining proportions. */
	const SCALE_PORTRAIT = 'portrait';
	
	/** Scale image to exact width contraining proportions. */
	const SCALE_LANDSCAPE = 'landscape';
	
	/** Determines the best scaling method based on the image. */
	const SCALE_AUTO = 'auto';
	
	/** Crops the image to exact size given. */
	const SCALE_CROP = 'crop';
	
	/** Max image dimension, in pixels. */
	const MAX_DIMENSION = 20000;
	
	/**
	 * An image identifier representing the image obtained from the given
	 * filename.
	 *
	 * @var resource
	 */
	protected $image;
	
	/**
	 * Stores the image type as defined by the IMAGETYPE_XXX constants.
	 *
	 * @var integer
	 */
	protected $image_type;
	
	/**
	 * Stores the image mime type.
	 *
	 * @var string
	 */
	protected $mime;
	
	/**
	 * Initializes the resizer.
	 *
	 * @param string $filename
	 * 
	 * @return void
	 * @throws Exception
	 */
	public function __construct($filename)
	{
		$image_info = getimagesize($filename);
		
		$this->image_type = $image_info[2];
		$this->mime = $image_info['mime'];
		
		switch ($this->image_type) {
			case IMAGETYPE_JPEG:
				$this->image = imagecreatefromjpeg($filename);
				break;
				
			case IMAGETYPE_GIF:
				$this->image = imagecreatefromgif($filename);
				break;
				
			case IMAGETYPE_PNG:
				$this->image = imagecreatefrompng($filename);
				break;
				
			default:
				throw new Exception('Unsupported image type.');
		}
	}
	
	/**
	 * Gets the image resource.
	 *
	 * @return resource
	 */
	public function image()
	{
		return $this->image;
	}
	
	/**
	 * Gets the image resource width.
	 *
	 * @return integer
	 */
	public function width()
	{
		return imagesx($this->image);
	}
	
	/**
	 * Gets the image resource height.
	 *
	 * @return integer
	 */
	public function height()
	{
		return imagesy($this->image);
	}
	
	/**
	 * Gets the image resource ratio.
	 *
	 * @return float
	 */
	public function ratio()
	{
		$w = $this->width();
		$h = $this->height();
		
		if ($w > $h) {
			return $w / $h;
		}
		else {
			return $h / $w;
		}
	}
	
	/**
	 * Gets the image type as defined by the IMAGETYPE_XXX constants.
	 * 
	 * @return integer
	 */
	public function image_type()
	{
		return $this->image_type;
	}
	
	/**
	 * Gets the image mime type.
	 * 
	 * @return string
	 */
	public function mime()
	{
		return $this->mime;
	}
	
	/**
	 * Gets the appropriate file extension.
	 * 
	 * @return string
	 */
	public function extension()
	{
		switch ($this->image_type) {
			case IMAGETYPE_JPEG:
				return 'jpg';
				
			case IMAGETYPE_GIF:
				return 'gif';
				
			case IMAGETYPE_PNG:
				return 'png';
		}
	}
	
	/**
	 * Resizes the image to the given dimensions.
	 *
	 * @param integer $width
	 * @param integer $height
	 * @param string  $scale
	 * 
	 * @return Resizer
	 * @throws Exception
	 */
	public function resize($width, $height, $scale = null)
	{
		if (null == $scale) {
			$scale = self::SCALE_AUTO;
		}
		
		$xy_scales = array(self::SCALE_EXACT, self::SCALE_CROP);
		if (in_array($scale, $xy_scales) && (empty($width) || empty($height))) {
			throw new Exception('Width and height must be non-zero integers');
		}
		else if (empty($width) && empty($height)) {
			throw new Exception('Width or height must be a non-zero integer');
		}
		
		$orig_x = $this->width();
		$orig_y = $this->height();
		
		// Handle auto scaling.
		if ($scale == self::SCALE_AUTO) {
			if (empty($height)) {
				$scale = self::SCALE_LANDSCAPE;
			}
			else if (empty($width)) {
				$scale = self::SCALE_PORTRAIT;
			}
			else if ($orig_y < $orig_x) {
				$scale = self::SCALE_LANDSCAPE;
			}
			else if ($orig_y > $orig_x) {
				$scale = self::SCALE_PORTRAIT;
			}
			else if ($height < $width) {
				$scale = self::SCALE_EXACT;
				$height = $width;
			}
			else {
				$scale = self::SCALE_EXACT;
				$width = $height;
			}
		}
		
		switch ($scale) {
			case self::SCALE_EXACT:
				break;
				
			case self::SCALE_PORTRAIT:
				$width = round($height * ($orig_x / $orig_y));
				$height = $height;
				break;
				
			case self::SCALE_LANDSCAPE:
				$height = round($width * ($orig_y / $orig_x));
				break;
				
			case self::SCALE_CROP:
				$given_width = $width;
				$given_height = $height;
				
				$ratio = max($height / $orig_y, $width / $orig_x);
				
				$height = round($orig_y * $ratio);
				$width  = round($orig_x * $ratio);
				break;
		}
		
		$new_image = imagecreatetruecolor($width, $height);
		$white = imagecolorallocate($new_image, 255, 255, 255);
		imagefill($new_image, 0, 0, $white);
		imagecopyresampled($new_image, $this->image, 0, 0, 0, 0, $width, $height, $orig_x, $orig_y);
		$this->image = $new_image;
		
		if ($scale == self::SCALE_CROP) {
			// Crop to the center.
			if ($width > $height) {
				$crop_start_x = ($width / 2) - ($given_width / 2);
				$crop_start_y = 0;
			}
			else {
				$crop_start_x = 0;
				$crop_start_y = ($height / 2) - ($given_height / 2);
			}
			
			$new_image = imagecreatetruecolor($given_width, $given_height);
			$white = imagecolorallocate($new_image, 255, 255, 255);
			imagefill($new_image, 0, 0, $white);
			imagecopyresampled(
				$new_image, $this->image, 0, 0, $crop_start_x, $crop_start_y,
				$given_width, $given_height, $given_width, $given_height
			);
			$this->image = $new_image;
		}
		
		return $this;
	}
	
	/**
	 * Saves the image to the given location at the given quality.
	 *
	 * @param string         $filename
	 * @param integer|string $image_type
	 * @param integer        $quality
	 * 
	 * @return boolean
	 */
	public function save($filename, $image_type = null, $quality = null)
	{
		$quality = (int) $quality;
		if (empty($quality)) {
			$quality = 100;
		}
		
		if (null === $image_type) {
			$image_type = $this->image_type;
		}
		
		switch ($image_type) {
			case 'gif':
			case IMAGETYPE_GIF:
				$success = imagegif($this->image, $filename);
				break;
				
			case 'png':
			case IMAGETYPE_PNG:
				$quality = 9 - round(($quality / 100) * 9);
				$success = imagepng($this->image, $filename, $quality);
				break;
				
			case 'jpeg':
			case 'jpg':
			case IMAGETYPE_JPEG:
			default:
				$success = imagejpeg($this->image, $filename, $quality);
				break;
		}
		
		return $success;
	}
	
	/**
	 * Frees the image resource from memory.
	 *
	 * @return void
	 */
	public function __destruct()
	{
		if (is_resource($this->image)) {
			imagedestroy($this->image);
		}
	}
}
