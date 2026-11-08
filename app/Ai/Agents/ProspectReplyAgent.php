<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

class ProspectReplyAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return 'Analyze a business reply to WildForce. Summarize the intent, recommend the next action, and draft a concise reply only when appropriate. Never claim to have sent a message. Recommend pausing follow-ups for opt-outs, disinterest, or explicit requests to stop.';
    }

    /**
     * Get the agent's structured output schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'intent' => $schema->string()->enum(['interested', 'not_interested', 'question', 'out_of_office', 'other'])->required(),
            'confidence' => $schema->string()->enum(['low', 'medium', 'high'])->required(),
            'summary' => $schema->string()->required(),
            'recommended_action' => $schema->string()->enum(['reply', 'book_demo', 'pause_follow_ups', 'close', 'wait'])->required(),
            'suggested_reply' => $schema->string()->nullable(),
            'should_pause_follow_ups' => $schema->boolean()->required(),
        ];
    }
}
