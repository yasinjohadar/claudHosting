<?php

namespace App\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

class ConnectionTestAgent implements Agent
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return 'Reply with OK only.';
    }
}

