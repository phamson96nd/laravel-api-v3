<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->string('featured_image')->nullable();
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->unsignedBigInteger('author_id');
            $table->json('meta_data')->nullable(); // For SEO and other metadata
            $table->integer('view_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('author_id')->references('id')->on('users')->onDelete('cascade');
            
            // Indexes
            $table->index(['status', 'published_at']);
            $table->index('author_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
