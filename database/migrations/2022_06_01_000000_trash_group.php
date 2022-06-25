<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TrashGroup extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trash_group', function (Blueprint $table) {
            $table->unsignedInteger('trash_group_id')->autoIncrement();
            $table->string('trash_group_name');
            $table->text('trash_group_address')->nullable();
            $table->unsignedInteger('trash_location_index');
            $table->dateTime('trash_group_created_at')->useCurrent();
            $table->dateTime('trash_group_updated_at')->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trash_group');
    }
}
