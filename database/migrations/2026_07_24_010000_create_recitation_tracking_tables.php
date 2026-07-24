<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qiraats', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('imam');
            $table->string('api_identifier')->nullable();
            $table->boolean('is_available')->default(true);
            $table->timestamps();
        });

        Schema::create('memorization_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('qiraat_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('surah_number')->default(1);
            $table->unsignedSmallInteger('ayah_number')->default(0);
            $table->unsignedSmallInteger('global_ayah_number')->default(0);
            $table->unsignedSmallInteger('page_number')->default(1);
            $table->timestamp('last_confirmed_at')->nullable();
            $table->timestamps();
            $table->unique(['student_id', 'qiraat_id']);
        });

        Schema::create('recitation_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('qiraat_id')->constrained()->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
            $table->index(['student_id', 'qiraat_id']);
        });

        Schema::create('ayah_confirmations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recitation_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('qiraat_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('surah_number');
            $table->unsignedSmallInteger('ayah_number');
            $table->unsignedSmallInteger('global_ayah_number');
            $table->timestamp('confirmed_at');
            $table->timestamps();
            $table->unique(['student_id', 'qiraat_id', 'global_ayah_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ayah_confirmations');
        Schema::dropIfExists('recitation_sessions');
        Schema::dropIfExists('memorization_progress');
        Schema::dropIfExists('qiraats');
    }
};
