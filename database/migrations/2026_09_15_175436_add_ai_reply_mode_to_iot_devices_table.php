<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('iot_devices', function (Blueprint $table) {
            // 応答モード設定
            $table->string('ai_reply_mode', 20)->default('command')->after('mic_sensitivity')
                  ->comment('AI応答モード: command(操作のみ), text(テキスト通知), voice(音声応答)');
            
            // （必要に応じて）音声キャラクターや音量設定
            $table->string('tts_voice', 50)->nullable()->after('ai_reply_mode')->comment('TTS音声モデル');
            $table->unsignedTinyInteger('speaker_volume')->default(50)->after('tts_voice')->comment('スピーカー音量');
        });
    }

    public function down(): void
    {
        Schema::table('iot_devices', function (Blueprint $table) {
            $table->dropColumn(['ai_reply_mode', 'tts_voice', 'speaker_volume']);
        });
    }
};
