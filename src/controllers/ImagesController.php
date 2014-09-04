<?php namespace Mmanos\Image;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\App;
use Dmyers\Storage\Storage;

/**
 * Images controller.
 * 
 * @author Mark Manos
 */
class ImagesController extends Controller
{
	/**
	 * Index action.
	 *
	 * @param string $part1
	 * @param string $part2
	 * 
	 * @return mixed
	 */
	public function getIndex($part1 = null, $part2 = null)
	{
		$size = empty($part2) ? null : $part1;
		$filename = empty($part2) ? $part1 : $part2;
		
		if (!$image = Image::where('filename', $filename)->first()) {
			App::abort(404);
		}
		
		if (!$path = $image->path($size)) {
			App::abort(404);
		}
		
		return Storage::render($path);
	}
}
