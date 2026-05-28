<?php

namespace Tests\Unit;

use App\Ai\Agents\BlogPostGeneratorAgent;
use Laravel\Ai\Enums\Lab;
use Tests\TestCase;

class BlogPostGeneratorAgentTest extends TestCase
{
    public function test_blog_post_agent_can_be_faked(): void
    {
        BlogPostGeneratorAgent::fake([
            [
                'title' => 'عنوان تجريبي',
                'excerpt' => 'مقتطف تجريبي',
                'content' => '<p>محتوى تجريبي</p>',
                'meta_title' => 'Meta Title',
            ],
        ]);

        $response = BlogPostGeneratorAgent::make()->prompt(
            'اكتب مقالا عن الاستضافة',
            provider: Lab::OpenRouter,
            model: 'openai/gpt-4o-mini'
        );

        $this->assertSame('عنوان تجريبي', $response['title']);
        BlogPostGeneratorAgent::assertPrompted('اكتب مقالا عن الاستضافة');
    }
}

