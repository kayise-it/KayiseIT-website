<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('whatsapp_e164', 20)->nullable()->after('show_whatsapp_floating');
        });

        DB::table('site_settings')->whereNull('whatsapp_e164')->update([
            'whatsapp_e164' => '27693907862',
        ]);
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn('whatsapp_e164');
        });
    }
};
