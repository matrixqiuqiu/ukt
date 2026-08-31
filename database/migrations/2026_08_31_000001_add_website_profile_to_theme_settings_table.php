<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('theme_settings', function (Blueprint $table) {
            $table->string('website_name')->nullable()->after('card_border');
            $table->string('website_short_name', 100)->nullable()->after('website_name');
            $table->string('website_tagline')->nullable()->after('website_short_name');
            $table->string('website_footer_text')->nullable()->after('website_tagline');
        });
    }

    public function down(): void
    {
        Schema::table('theme_settings', function (Blueprint $table) {
            $table->dropColumn(['website_name','website_short_name','website_tagline','website_footer_text']);
        });
    }
};
