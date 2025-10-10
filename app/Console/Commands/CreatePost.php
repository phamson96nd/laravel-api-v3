<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreatePost extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-post';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new post';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $title = Str::random(16);
        $content = Str::random(32);
        $authorId = 1;
        $status = "published";
        $excerpt = "";

        try {
            $post = Post::create([
                'title' => $title,
                'content' => $content,
                'excerpt' => $excerpt ?: null,
                'status' => $status,
                'author_id' => $authorId,
                'published_at' => $status === 'published' ? now() : null,
            ]);

            $this->info("Post created successfully!");
            $this->line("ID: {$post->id}");
            $this->line("Title: {$post->title}");
            $this->line("Slug: {$post->slug}");
            $this->line("Status: {$post->status}");
            $this->line("Author: {$post->author->name}");
            $this->line("Created: {$post->created_at}");
            
        } catch (\Exception $e) {
            $this->error("Failed to create post: " . $e->getMessage());
        }
    }
}
