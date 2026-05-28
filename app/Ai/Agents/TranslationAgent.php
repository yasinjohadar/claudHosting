<?php

namespace App\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

class TranslationAgent implements Agent
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return 'You are a professional translator focused on preserving meaning, tone, and context.';
    }
}

