<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('theme_settings', function (Blueprint $table) {
            $table->id();
            $table->string('sidebar_bg')->default('#1e293b');
            $table->string('sidebar_text')->default('#94a3b8');
            $table->string('sidebar_active_text')->default('#ffffff');
            $table->string('sidebar_active_bg')->default('#4f46e5');
            $table->string('sidebar_hover_bg')->default('#334155');
            $table->string('navbar_bg')->default('#ffffff');
            $table->string('navbar_text')->default('#1e293b');
            $table->string('navbar_border')->default('#e2e8f0');
            $table->string('primary_color')->default('#4f46e5');
            $table->string('logo_text')->default('#ffffff');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('theme_settings');
    }
};
