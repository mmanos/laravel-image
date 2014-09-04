<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateImagesTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('images', function ($table) {
			$table->increments('id');
			$table->string('filename');
			$table->string('original_filename');
			$table->string('type');
			$table->integer('filesize');
			$table->integer('width');
			$table->integer('height');
			$table->float('ratio');
			$table->text('sizes')->nullable();
			$table->timestamps();
			$table->unique('filename', 'filename');
			$table->index('original_filename', 'original_filename');
			$table->index('type', 'type');
			$table->index('ratio', 'ratio');
			$table->index('created_at', 'newest_images');
			$table->index('filesize', 'largest_images');
		});
	}
	
	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('images');
	}
}
