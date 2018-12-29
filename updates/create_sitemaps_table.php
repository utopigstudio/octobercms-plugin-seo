<?php namespace Utopigs\Seo\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateSitemapsTable extends Migration
{
    public function up()
    {
        Schema::create('utopigs_seo_sitemaps', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('theme')->nullable()->index();
            $table->mediumtext('data')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('utopigs_seo_sitemaps');
    }
}
