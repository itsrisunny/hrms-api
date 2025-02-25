<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSoftDeletesToSMEsTable extends Migration
{
    public function up()
    {
        Schema::table('s_m_e_s', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::table('s_m_e_s', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
}
