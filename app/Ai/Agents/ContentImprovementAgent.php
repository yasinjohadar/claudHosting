<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

class ContentImprovementAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return 'You improve Arabic and English content for clarity, grammar, and style while preserving meaning.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'content' => $schema->string()->required(),
            'suggestions' => $schema->array(
                $schema->string()
            ),
            'errors' => $schema->array(
                $schema->string()
            ),
            'corrected' => $schema->string(),
        ];
    }
}

