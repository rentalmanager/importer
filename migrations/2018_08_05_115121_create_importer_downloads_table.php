<?php
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateImporterDownloadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return  void
     */
    public function up()
    {

        // Create table for the property identifications
        Schema::create('importer_downloads', function(Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('provider_id')->nullable()->index();
            $table->text('data')->nullable();
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
        Schema::dropIfExists('importer_downloads');
    }
}
