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
        Schema::table('users', function(Blueprint $table) {
            $table->string('avatar', 255)->nullable()->after('password');
            $table->string('fullname', 255)->nullable()->after('avatar');
            $table->string('website', 255)->nullable()->after('fullname');
            $table->string('github', 255)->nullable()->after('website');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function(Blueprint $table) {
            $table->dropColumn('avatar', 255);
            $table->dropColumn('fullname', 255);
            $table->dropColumn('website', 255);
            $table->dropColumn('github', 255);
        });
    }
};
