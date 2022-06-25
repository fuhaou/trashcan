<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TrashType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trash_type', function (Blueprint $table) {
            $table->unsignedInteger('trash_type_id')->autoIncrement();
            $table->string('trash_type_name');
            $table->string('trash_type_color')->nullable();
            $table->dateTime('trash_type_created_at')->useCurrent();
            $table->dateTime('trash_type_updated_at')->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trash_type');
    }
}
