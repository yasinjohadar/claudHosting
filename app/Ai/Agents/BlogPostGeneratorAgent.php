<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

class BlogPostGeneratorAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
You are a professional Arabic-first content writer.
Generate a complete blog post package with SEO fields.
Return clean, publication-ready output that matches the requested schema exactly.
PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->required(),
            'excerpt' => $schema->string()->required(),
            'content' => $schema->string()->required(),
            'meta_title' => $schema->string(),
            'meta_description' => $schema->string(),
            'focus_keyword' => $schema->string(),
            'og_title' => $schema->string(),
            'og_description' => $schema->string(),
            'twitter_title' => $schema->string(),
            'twitter_description' => $schema->string(),
        ];
    }
}

