<?php namespace Utopigs\Seo\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::table('utopigs_seo_data', function($table)
        {
            $table->text('title')->nullable()->change();
            $table->text('keywords')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('utopigs_seo_data', function($table)
        {
            $table->string('title')->change();
            $table->string('keywords')->nullable()->change();
        });
    }
};
