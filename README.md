# Image Package for Laravel 4

This package provides the ability to conveniently work with uploaded images.
It includes an images table and model, and has the ability to provide multiple
versions of the same image with different dimensions, generated on demand.

## Dependencies

This package relies on the [laravel-storage](https://github.com/dmyers/laravel-storage) to manage the image files, which supports writing to the local filesystem or the Amazon S3 service.

## Installation Via Composer

Add this to you composer.json file, in the require object:

```javascript
"mmanos/laravel-image": "dev-master"
```

After that, run composer install to install the package.

Add the service provider to `app/config/app.php`, within the `providers` array.

```php
'providers' => array(
	// ...
	'Mmanos\Image\ImageServiceProvider',
)
```

Add a class alias to `app/config/app.php`, within the `aliases` array.

```php
'aliases' => array(
	// ...
	'Image' => 'Mmanos\Image\Image',
)
```

## Configuration

Publish the default config file to your application so you can make modifications.

```console
$ php artisan config:publish mmanos/laravel-image
```

Run the database migrations for this package.

```console
$ php artisan migrate --package="mmanos/laravel-image"
```

> **Note:** Don't forget to create the writable directory used by the Storage package, if using the local filesystem driver.

## Usage

#### Creating Images

Create an image from image data:

```php
$image = Image::put($contents);
```

Create an image from an uploaded image file:

```php
$image = Image::upload($_FILES['image']);
```

Create an image from a file on the filesystem:

```php
$image = Image::copy($path);
```

Create an image from a URL to an image:

```php
$image = Image::copyUrl($url);
```

#### Using Images

Get URL to the original image:

```php
$url = $image->url();
```

Get URL to image constrained to 128px (best fit, maintaining aspect ratio):

```php
$url = $image->url('128');
```

Get URL to image constrained to 128px in height (maintaining aspect ratio):

```php
$url = $image->url('128h');
```

Get URL to image constrained to 128px in width (maintaining aspect ratio):

```php
$url = $image->url('128w');
```

Get URL to image cropped to a 128px square:

```php
$url = $image->url('128s');
```

> **Note:** You may pass any arbitrary integer value for the image dimension.
