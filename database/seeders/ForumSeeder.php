<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Forum\ForumCategory;
use App\Models\Forum\ForumThread;
use App\Models\Forum\ForumPoll;
use App\Models\Forum\ForumPollOption;
use App\Models\Forum\ForumComment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ForumSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure at least one user exists
        $user = User::first();
        if (!$user) {
            $user = User::factory()->create([
                'email' => 'demo@example.com',
                'first_name' => 'Demo',
                'last_name' => 'User',
                'password' => bcrypt('password'),
            ]);
        }

        // Categories with colors matching your UI pill mapping
        $cats = [
            ['name'=>'Showcase','slug'=>'showcase','color_bg'=>'rgba(167,139,250,.12)','color_fg'=>'#E9D5FF','color_border'=>'#A78BFA'],
            ['name'=>'Jobs','slug'=>'jobs','color_bg'=>'rgba(74,222,128,.12)','color_fg'=>'#BBF7D0','color_border'=>'#4ADE80'],
            ['name'=>'Question','slug'=>'question','color_bg'=>'rgba(250,204,21,.12)','color_fg'=>'#FEF08A','color_border'=>'#FACC15'],
            ['name'=>'Feedback','slug'=>'feedback','color_bg'=>'rgba(110,231,183,.12)','color_fg'=>'#A7F3D0','color_border'=>'#34D399'],
            ['name'=>'AI Tools','slug'=>'ai-tools','color_bg'=>'rgba(147,197,253,.12)','color_fg'=>'#BFDBFE','color_border'=>'#93C5FD'],
            ['name'=>'Announcements','slug'=>'announcements','color_bg'=>'rgba(253,186,116,.12)','color_fg'=>'#FED7AA','color_border'=>'#FDBA74'],
            ['name'=>'Off-Topic','slug'=>'off-topic','color_bg'=>'rgba(212,212,212,.10)','color_fg'=>'#E5E7EB','color_border'=>'#A3A3A3'],
        ];
        foreach ($cats as $c) {
            ForumCategory::firstOrCreate(['slug'=>$c['slug']], $c);
        }

        $aiTools = ForumCategory::where('slug','ai-tools')->first();
        $showcase = ForumCategory::where('slug','showcase')->first();

        // Text thread
        $t1 = ForumThread::create([
            'user_id' => $user->id,
            'category_id' => $showcase?->id,
            'title' => 'Just hit $10k MRR with my SaaS — AMA',
            'post_type' => 'text',
            'excerpt' => '8 months, $200 budget, and a lot of mistakes.',
            'body_html' => '<p>Ask me anything about the journey, the stack, and distribution.</p>',
        ]);

        // Image thread
        $t2 = ForumThread::create([
            'user_id' => $user->id,
            'category_id' => $aiTools?->id,
            'title' => 'Can we all take a moment to appreciate this',
            'post_type' => 'image',
            'media_url' => 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?auto=format&fit=crop&w=1600&q=80',
            'media_alt' => 'Assorted fresh fruits and vegetables on a table',
            'excerpt' => 'Whole foods > ultra-processed snacks',
        ]);

        // Poll thread
        $t3 = ForumThread::create([
            'user_id' => $user->id,
            'category_id' => $aiTools?->id,
            'title' => 'Best AI tools for content creation in 2025?',
            'post_type' => 'poll',
            'body_html' => '<p>Vote your favorites. Share why in comments!</p>',
        ]);

        $poll = ForumPoll::create([
            'thread_id' => $t3->id,
            'multiple'  => true,
        ]);

        $opts = ['Research (Perplexity)','Outline (Notion AI)','Draft (Claude)','Polish (Grammarly)','Video (CapCut)'];
        foreach ($opts as $i => $label) {
            ForumPollOption::create([
                'poll_id' => $poll->id,
                'label'   => $label,
                'position'=> $i+1,
            ]);
        }

        // Comments (nested)
        $c1 = ForumComment::create([
            'thread_id' => $t1->id,
            'user_id'   => $user->id,
            'body_html' => '<p>Congrats! What was your distribution channel?</p>',
        ]);

        ForumComment::create([
            'thread_id' => $t1->id,
            'user_id'   => $user->id,
            'parent_id' => $c1->id,
            'body_html' => '<p>Mostly Twitter + cold email to early adopters.</p>',
        ]);

        // Increment counters
        ForumThread::whereKey($t1->id)->update(['comments_count' => 2]);
    }
}
