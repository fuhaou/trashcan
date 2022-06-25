<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TrashInfo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trash_info', function (Blueprint $table) {
            $table->unsignedInteger('trash_info_id')->autoIncrement();
            $table->unsignedInteger('user_index')->index();
            $table->unsignedInteger('trash_index')->index();
            $table->decimal('trash_info_weight')->default(0);
            $table->dateTime('trash_info_created_at')->useCurrent();
            $table->dateTime('trash_info_updated_at')->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trash_info');
    }
}
