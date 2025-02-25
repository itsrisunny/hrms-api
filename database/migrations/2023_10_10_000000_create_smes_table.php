<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSMEsTable extends Migration
{
    public function up()
    {
        Schema::create('s_m_e_s', function (Blueprint $table) {
            $table->id();
            $table->string('sme_name');
            $table->string('sme_id', 191)->unique(); // Reduced length
            $table->string('sme_phone');
            $table->string('sme_email', 191)->unique(); // Reduced length
            $table->string('sme_expertise_area');
            $table->string('sme_linkedin_profile')->nullable();
            $table->string('sme_temporary_email')->nullable();
            $table->string('sme_temporary_password')->nullable();
            $table->boolean('enable_temporary_values');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('s_m_e_s');
    }
}
