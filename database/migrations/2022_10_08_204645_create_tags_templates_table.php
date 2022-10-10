<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tags_templates', function (Blueprint $table) {
            $table->unsignedInteger('id', true)->unique('id');
            $table->unsignedInteger('template_id');
            $table->unsignedInteger('tag_id');
            $table->timestamps();

            $table->foreign('template_id')->references('id')->on('templates')->cascadeOnDelete();
            $table->foreign('tag_id')->references('id')->on('tags')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tags_templates');
    }
};
