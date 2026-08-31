<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('theme_settings', function (Blueprint $table) {
            $table->string('content_bg')->nullable()->default('#f8fafc')->after('primary_color');
            $table->string('content_text')->nullable()->default('#1e293b')->after('content_bg');
            $table->string('card_bg')->nullable()->default('#ffffff')->after('content_text');
            $table->string('card_border')->nullable()->default('#e2e8f0')->after('card_bg');
        });
    }

    public function down(): void
    {
        Schema::table('theme_settings', function (Blueprint $table) {
            $table->dropColumn(['content_bg', 'content_text', 'card_bg', 'card_border']);
        });
    }
};
