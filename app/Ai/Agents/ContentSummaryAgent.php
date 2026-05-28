<?php

namespace App\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

class ContentSummaryAgent implements Agent
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return 'You summarize content precisely while preserving key facts and intent.';
    }
}

