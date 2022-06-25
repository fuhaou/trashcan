<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Trash extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trash', function (Blueprint $table) {
            $table->unsignedInteger('trash_id')->autoIncrement();
            $table->string('trash_name')->nullable();
            $table->string('trash_qr');
            $table->unsignedInteger('trash_type_index')->index();
            $table->unsignedInteger('trash_location_index')->index();
            $table->unsignedInteger('trash_group_index')->index();
            $table->dateTime('trash_created_at')->useCurrent();
            $table->dateTime('trash_updated_at')->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trash');
    }
}
