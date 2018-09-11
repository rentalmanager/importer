<?php
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;


class CreateImporterJobsTable extends Migration {
    public function up()
    {

        // Create table for the property identifications
        Schema::create('importer_jobs', function(Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('provider_id')->nullable()->index();
            $table->boolean('downloader')->nullable();
            $table->boolean('parser')->nullable();
            $table->timestamps();
        });

    }
}
