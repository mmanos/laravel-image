<?php

return array(

	/*
	|--------------------------------------------------------------------------
	| Images Controller Route
	|--------------------------------------------------------------------------
	|
	| Specify the route path to use for the ImagesController.
	| Leave empty to disable the route.
	|
	*/

	'route' => 'images/{part1}/{part2?}',

	/*
	|--------------------------------------------------------------------------
	| Images Table
	|--------------------------------------------------------------------------
	|
	| Specify the name of the database table to use for images.
	|
	*/

	'table' => 'images',

	/*
	|--------------------------------------------------------------------------
	| Base Storage Path
	|--------------------------------------------------------------------------
	|
	| Specify the base path to use for images saved using the Storage package.
	|
	*/

	'storage_base_path' => 'images',

	/*
	|--------------------------------------------------------------------------
	| Resized Image Quality
	|--------------------------------------------------------------------------
	|
	| Specify the image quality of the resized images, from 1 to 100.
	|
	*/

	'image_resize_quality' => 90,

);
