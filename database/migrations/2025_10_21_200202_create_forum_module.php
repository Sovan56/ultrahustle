<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ---------- forum_categories ----------
        Schema::create('forum_categories', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->string('slug')->unique();
            $t->string('color_bg')->nullable();     // for UI pill
            $t->string('color_fg')->nullable();     // for UI pill
            $t->string('color_border')->nullable(); // for UI pill
            $t->timestamps();
        });

        // ---------- forum_threads ----------
        Schema::create('forum_threads', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('category_id')->nullable()->constrained('forum_categories')->nullOnDelete();
            $t->string('title');
            $t->enum('post_type', ['text','image','video','poll'])->default('text');
            $t->text('excerpt')->nullable();
            $t->longText('body_html')->nullable();
            // media (for image/video)
            $t->string('media_url')->nullable();
            $t->string('media_poster')->nullable();
            $t->string('media_alt')->nullable();
            // counters for performance
            $t->unsignedBigInteger('likes_count')->default(0);
            $t->unsignedBigInteger('comments_count')->default(0);
            $t->unsignedBigInteger('saves_count')->default(0);
            $t->unsignedBigInteger('shares_count')->default(0);
            $t->timestamps();
            $t->index(['category_id','created_at']);
        });

        // ---------- forum_polls (one per thread when post_type = poll) ----------
        Schema::create('forum_polls', function (Blueprint $t) {
            $t->id();
            $t->foreignId('thread_id')->unique()->constrained('forum_threads')->cascadeOnDelete();
            $t->boolean('multiple')->default(false);
            $t->unsignedBigInteger('total_votes')->default(0);
            $t->timestamps();
        });

        Schema::create('forum_poll_options', function (Blueprint $t) {
            $t->id();
            $t->foreignId('poll_id')->constrained('forum_polls')->cascadeOnDelete();
            $t->string('label');
            $t->unsignedBigInteger('votes')->default(0);
            $t->unsignedInteger('position')->default(0);
            $t->timestamps();
            $t->index(['poll_id','position']);
        });

        Schema::create('forum_poll_votes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('poll_id')->constrained('forum_polls')->cascadeOnDelete();
            $t->foreignId('option_id')->constrained('forum_poll_options')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->timestamps();
            // A user can vote multiple options only if poll.multiple = true.
            // We enforce "at least one vote per user per poll" uniqueness with:
            $t->unique(['poll_id','user_id','option_id'], 'poll_user_option_unique');
            $t->index(['poll_id','user_id']);
        });

        // ---------- forum_comments (nested) ----------
        Schema::create('forum_comments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('thread_id')->constrained('forum_threads')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('parent_id')->nullable()->constrained('forum_comments')->cascadeOnDelete();
            $t->longText('body_html'); // we’ll store formatted HTML from toolbar (not CKEditor)
            $t->unsignedBigInteger('likes_count')->default(0);
            $t->timestamps();
            $t->index(['thread_id','parent_id','created_at']);
        });

        // ---------- likes ----------
        Schema::create('forum_thread_likes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('thread_id')->constrained('forum_threads')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->timestamps();
            $t->unique(['thread_id','user_id']);
        });

        Schema::create('forum_comment_likes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('comment_id')->constrained('forum_comments')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->timestamps();
            $t->unique(['comment_id','user_id']);
        });

        // ---------- saves (bookmarks) ----------
        Schema::create('forum_thread_saves', function (Blueprint $t) {
            $t->id();
            $t->foreignId('thread_id')->constrained('forum_threads')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->timestamps();
            $t->unique(['thread_id','user_id']);
        });

        // ---------- follows (follow an author) ----------
        Schema::create('forum_follows', function (Blueprint $t) {
            $t->id();
            $t->foreignId('follower_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('followed_id')->constrained('users')->cascadeOnDelete();
            $t->timestamps();
            $t->unique(['follower_id','followed_id']);
        });

        // ---------- reports (optional for "Report" button) ----------
        Schema::create('forum_reports', function (Blueprint $t) {
            $t->id();
            $t->foreignId('thread_id')->nullable()->constrained('forum_threads')->nullOnDelete();
            $t->foreignId('comment_id')->nullable()->constrained('forum_comments')->nullOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('reason')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
        });

        // ---------- shares counter (if you want to track per-user) ----------
        Schema::create('forum_thread_shares', function (Blueprint $t) {
            $t->id();
            $t->foreignId('thread_id')->constrained('forum_threads')->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('channel')->nullable(); // twitter, facebook, copy_link, etc.
            $t->timestamps();
            $t->index(['thread_id','channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_thread_shares');
        Schema::dropIfExists('forum_reports');
        Schema::dropIfExists('forum_follows');
        Schema::dropIfExists('forum_thread_saves');
        Schema::dropIfExists('forum_comment_likes');
        Schema::dropIfExists('forum_thread_likes');
        Schema::dropIfExists('forum_comments');
        Schema::dropIfExists('forum_poll_votes');
        Schema::dropIfExists('forum_poll_options');
        Schema::dropIfExists('forum_polls');
        Schema::dropIfExists('forum_threads');
        Schema::dropIfExists('forum_categories');
    }
};
