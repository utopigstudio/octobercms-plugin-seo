<?php namespace Utopigs\Seo\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class AddNoIndexField extends Migration
{
    public function up()
    {
        Schema::table('utopigs_seo_data', function($table)
        {
            $table->string('og_type')->nullable()->default('default')->after('reference');
        });
    }

    public function down()
    {
        Schema::table('utopigs_seo_data', function($table)
        {
            $table->dropColumn('og_type');
        });
    }
}
