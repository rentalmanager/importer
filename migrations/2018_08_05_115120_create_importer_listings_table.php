<?php
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateImporterListingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return  void
     */
    public function up()
    {

        // Create table for the property identifications
        Schema::create('importer_listings', function(Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('provider_id')->nullable()->index();
            $table->unsignedInteger('property_id')->nullable()->index();
            $table->string('foreign_id')->nullable()->index();
            $table->enum('status', [
                'new',
                'unmodified',
                'updated',
                'rejected',
                'removed',
                'blocked'
            ])->nullable();
            $table->string('hash')->nullable(); // the action type
            $table->longText('updated_fields')->nullable();
            $table->enum('error_level', ['none', 'severe', 'warning'])->nullable();
            $table->longText('errors')->nullable();
            $table->longText('data')->nullable();
            $table->longText('location_data')->nullable();
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
        Schema::dropIfExists('importer_listings');
    }
}
