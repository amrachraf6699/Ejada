<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificate_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('background_path');
            $table->unsignedInteger('width');
            $table->unsignedInteger('height');
            $table->json('canvas_json');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('issued_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('certificate_template_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('issued_by')->constrained('users')->cascadeOnDelete();
            $table->string('student_name_snapshot');
            $table->string('image_path');
            $table->json('metadata_json')->nullable();
            $table->timestamp('issued_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issued_certificates');
        Schema::dropIfExists('certificate_templates');
    }
};
