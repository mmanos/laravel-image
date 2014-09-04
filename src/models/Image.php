<?php namespace Mmanos\Image;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Dmyers\Storage\Storage;
use Exception;

class Image extends Model
{
	protected $hidden = array('updated_at');
	protected $guarded = array('id');
	
	public function getTable()
	{
		return Config::get('laravel-image::table', 'images');
	}
	
	public function getSizesAttribute($value)
	{
		return empty($value) ? array() : json_decode($value, true);
	}
	
	public function setSizesAttribute($value)
	{
		$this->attributes['sizes'] = json_encode($value);
	}
	
	public function url($size = null)
	{
		$better_size = $this->moreRelevantSize($size);
		if (false !== $better_size) {
			return $this->url($better_size);
		}
		
		$size = $size ? $size : 'original';
		
		return array_get(
			$this->sizes,
			$size,
			action('Mmanos\Image\ImagesController@getIndex', array($size, $this->filename))
		);
	}
	
	public function path($size = null)
	{
		if (null === $size) {
			return $this->pathFromSize();
		}
		
		$size = (string) $size;
		if (array_key_exists($size, $this->sizes)) {
			return $this->pathFromSize($size);
		}
		
		return $this->generate($size);
	}
	
	protected function generate($size)
	{
		$size = $original_size = (string) $size;
		if (empty($size)) {
			return false;
		}
		
		$size_num = (int) $size;
		if (!is_numeric($size_num) || $size_num <= 0 || $size_num > Resizer::MAX_DIMENSION) {
			return false;
		}
		
		$better_size = $this->moreRelevantSize($size);
		if (false !== $better_size) {
			return $this->path($better_size);
		}
		
		switch (true) {
			case $size[strlen($size) - 1] == 's':
				$width = $height = $size_num;
				$mode  = Resizer::SCALE_CROP;
				$size  = $size_num . 's';
				break;
				
			case $size[strlen($size) - 1] == 'w':
				$width  = $size_num;
				$height = null;
				$mode   = Resizer::SCALE_LANDSCAPE;
				$size   = $size_num . 'w';
				break;
				
			case $size[strlen($size) - 1] == 'h':
				$width  = null;
				$height = $size_num;
				$mode   = Resizer::SCALE_PORTRAIT;
				$size   = $size_num . 'h';
				break;
				
			default:
				$width = $height = $size_num;
				$mode  = Resizer::SCALE_AUTO;
				$size  = $size_num . '';
				break;
		}
		
		if ($size !== $original_size) {
			return false;
		}
		
		$fullsize_stream = Storage::get($this->pathFromSize());
		if (!$fullsize_stream) {
			return false;
		}
		
		$temp_file = tempnam(sys_get_temp_dir(), 'laravel-image');
		File::put($temp_file, $fullsize_stream);
		if (!File::exists($temp_file)) {
			return false;
		}
		
		try {
			$resizer = new Resizer($temp_file);
			$resizer->resize($width, $height, $mode);
			
			if (!$resizer->save($temp_file, null, Config::get('laravel-image::image_resize_quality', 100))) {
				throw Exception('Error resizing file.');
			}
			
			$stream = File::get($temp_file);
			if (!$stream) {
				throw Exception('Error retrieving file.');
			}
		} catch (Exception $e) {
			File::delete($temp_file);
			return false;
		}
		
		$new_path = $this->pathFromSize($size);
		
		Storage::put($new_path, $stream);
		
		File::delete($temp_file);
		
		$sizes = $this->sizes;
		$sizes[$size] = Storage::url($new_path);
		$this->sizes = $sizes;
		$this->save();
		
		return $new_path;
	}
	
	protected function pathFromSize($size = null)
	{
		if (null === $size) {
			return Config::get('laravel-image::storage_base_path') . '/' . $this->filename;
		}
		
		return Config::get('laravel-image::storage_base_path')
			. '/' . $size
			. '/' . $this->filename;
	}
	
	protected function moreRelevantSize($size)
	{
		$size = (string) $size;
		if (empty($size)) {
			return false;
		}
		
		$size_num = (int) $size;
		if (!is_numeric($size_num) || $size_num <= 0 || $size_num > Resizer::MAX_DIMENSION) {
			return false;
		}
		
		switch (true) {
			case $size[strlen($size) - 1] == 's':
				$max_size = min($this->width, $this->height);
				if ($size_num > $max_size) {
					return $max_size . 's';
				}
				break;
				
			case $size[strlen($size) - 1] == 'w':
				$max_size = $this->width;
				if ($size_num > $max_size) {
					return null;
				}
				break;
				
			case $size[strlen($size) - 1] == 'h':
				$max_size = $this->height;
				if ($size_num > $max_size) {
					return null;
				}
				break;
				
			default:
				$max_size = max($this->width, $this->height);
				if ($size_num > $max_size) {
					return null;
				}
				break;
		}
		
		return false;
	}
	
	public static function put($contents, array $data = array())
	{
		$path = tempnam(sys_get_temp_dir(), 'laravel-image-put');
		
		File::put($path, $contents);
		
		$image = static::copy($path, $data);
		
		File::delete($path);
		
		return $image;
	}
	
	public static function upload($file, array $data = array())
	{
		$path = array_get($file, 'tmp_name');
		$original_filename = array_get($file, 'name');
		
		if (empty($path) || empty($original_filename)) {
			return false;
		}
		
		if (empty($data['original_filename'])) {
			$data['original_filename'] = $original_filename;
		}
		
		return static::copy($path, $data);
	}
	
	public static function copyUrl($url, array $data = array())
	{
		if (!filter_var($url, FILTER_VALIDATE_URL)) {
			return false;
		}
		
		$url_parts = parse_url($url);
		if (!$url_parts) {
			return false;
		}
		
		$path = tempnam(sys_get_temp_dir(), 'laravel-image-copy');
		if (!File::copy($url, $path)) {
			File::delete($path);
			return false;
		}
		
		if (empty($data['original_filename'])) {
			$data['original_filename'] = basename(array_get($url_parts, 'path'));
		}
		
		$image = static::copy($path, $data);
		
		File::delete($path);
		
		return $image;
	}
	
	public static function copy($path, array $data = array())
	{
		if (!File::exists($path)) {
			return false;
		}
		
		// Clear file status cache (prevents empty filesize).
		clearstatcache();
		
		try {
			$resizer = new Resizer($path);
		} catch (Exception $e) {
			dar($e);
			return false;
		}
		
		$image = new static;
		$image->filename = md5(Str::random(32)) . '.' . $resizer->extension();
		$image->original_filename = array_get($data, 'original_filename');
		$image->type = $resizer->mime();
		$image->filesize = File::size($path);
		$image->width = $resizer->width();
		$image->height = $resizer->height();
		$image->ratio = $resizer->ratio();
		
		if (empty($image->original_filename)) {
			$image->original_filename = basename($path);
			if (false === strstr($image->original_filename, '.')) {
				$image->original_filename .= '.' . $resizer->extension();
			}
		}
		
		Storage::upload($path, $image->pathFromSize());
		if (!Storage::exists($image->pathFromSize())) {
			return false;
		}
		
		$image->sizes = array(
			'original' => Storage::url($image->pathFromSize()),
		);
		
		$image->save();
		
		return $image;
	}
}
