<?php

use App\Models\MemorizationProgress;
use App\Models\RecitationAttempt;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recitation_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('narration_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('attempt_number');
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['student_id', 'narration_id', 'attempt_number']);
            $table->index(['student_id', 'narration_id', 'completed_at']);
        });
        Schema::table('memorization_progress', function (Blueprint $table) { $table->foreignId('recitation_attempt_id')->nullable()->after('narration_id')->constrained()->cascadeOnDelete(); });
        Schema::table('recitation_sessions', function (Blueprint $table) { $table->foreignId('recitation_attempt_id')->nullable()->after('narration_id')->constrained()->cascadeOnDelete(); });
        Schema::table('ayah_confirmations', function (Blueprint $table) { $table->foreignId('recitation_attempt_id')->nullable()->after('narration_id')->constrained()->cascadeOnDelete(); });

        MemorizationProgress::cursor()->each(function (MemorizationProgress $progress): void {
            $attempt = RecitationAttempt::create(['student_id' => $progress->student_id, 'narration_id' => $progress->narration_id, 'attempt_number' => 1, 'started_at' => $progress->created_at ?? now(), 'completed_at' => $progress->is_complete ? ($progress->last_confirmed_at ?? $progress->updated_at ?? now()) : null]);
            $progress->update(['recitation_attempt_id' => $attempt->id]);
            DB::table('recitation_sessions')->where('student_id', $progress->student_id)->where('narration_id', $progress->narration_id)->update(['recitation_attempt_id' => $attempt->id]);
            DB::table('ayah_confirmations')->where('student_id', $progress->student_id)->where('narration_id', $progress->narration_id)->update(['recitation_attempt_id' => $attempt->id]);
        });

        Schema::table('memorization_progress', function (Blueprint $table) { $table->dropUnique(['student_id', 'narration_id']); $table->unique('recitation_attempt_id'); });
        Schema::table('ayah_confirmations', function (Blueprint $table) { $table->dropUnique(['student_id', 'narration_id', 'global_ayah_number']); $table->unique(['recitation_attempt_id', 'global_ayah_number']); });
    }

    public function down(): void
    {
        Schema::table('ayah_confirmations', function (Blueprint $table) { $table->dropUnique(['recitation_attempt_id', 'global_ayah_number']); $table->dropConstrainedForeignId('recitation_attempt_id'); });
        Schema::table('recitation_sessions', function (Blueprint $table) { $table->dropConstrainedForeignId('recitation_attempt_id'); });
        Schema::table('memorization_progress', function (Blueprint $table) { $table->dropUnique(['recitation_attempt_id']); $table->unique(['student_id', 'narration_id']); $table->dropConstrainedForeignId('recitation_attempt_id'); });
        Schema::dropIfExists('recitation_attempts');
    }
};
