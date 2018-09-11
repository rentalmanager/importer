<?php
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateImporterParserJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return  void
     */
    public function up()
    {

        // Create table for the property identifications
        Schema::create('importer_parser_jobs', function(Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('provider_id')->nullable()->index();
            $table->text('feed_file')->nullable();
            $table->boolean('error')->default(false);
            $table->string('error_msg')->nullable();
            $table->longText('data')->nullable();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return  void
     */
    public function down()
    {
        Schema::dropIfExists('importer_parser_jobs');
    }
}
