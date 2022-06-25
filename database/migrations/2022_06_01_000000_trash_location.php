<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TrashLocation extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trash_location', function (Blueprint $table) {
            $table->unsignedInteger('trash_location_id')->autoIncrement();
            $table->string('trash_location_name');
            $table->string('trash_location_address')->nullable();
            $table->dateTime('trash_location_created_at')->useCurrent();
            $table->dateTime('trash_location_updated_at')->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trash_location');
    }
}
