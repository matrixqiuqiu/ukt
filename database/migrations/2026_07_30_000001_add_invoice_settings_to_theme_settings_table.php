<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('theme_settings', function (Blueprint $table) {
            $table->string('invoice_institution_name')->nullable();
            $table->string('invoice_institution_address')->nullable();
            $table->string('invoice_institution_phone')->nullable();
            $table->string('invoice_institution_email')->nullable();
            $table->string('invoice_institution_website')->nullable();
            $table->string('invoice_logo')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('theme_settings', function (Blueprint $table) {
            $table->dropColumn([
                'invoice_institution_name',
                'invoice_institution_address',
                'invoice_institution_phone',
                'invoice_institution_email',
                'invoice_institution_website',
                'invoice_logo',
            ]);
        });
    }
};
