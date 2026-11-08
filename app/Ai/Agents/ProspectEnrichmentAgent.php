<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Laravel\Ai\Providers\Tools\ProviderTool;
use Laravel\Ai\Providers\Tools\WebSearch;
use Stringable;

class ProspectEnrichmentAgent implements Agent, HasStructuredOutput, HasTools
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<'INSTRUCTIONS'
You enrich an already discovered Spanish fitness business for WildForce. Use web search before answering and assess only the business in the prompt.

Return only facts supported by source URLs. Do not invent a website, services, activity, team size, contact data, or business pain. Do not return private personal data or recommend contacting anyone. A public business phone and public social profile may be returned only when directly supported by a source.

Set is_match to false when the business is not a Spanish personal trainer, boutique gym, or gym that is a plausible WildForce customer. Keep the rationale, signals, and suggested angle factual, concise, and supported by the listed sources. Return fewer details rather than unsupported details.
INSTRUCTIONS;
    }

    /**
     * Get the tools available to the agent.
     *
     * @return list<Agent|ProviderTool>
     */
    public function tools(): iterable
    {
        return [
            (new WebSearch)->max(5)->location(country: 'ES'),
        ];
    }

    /**
     * Get the agent's structured output schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'is_match' => $schema->boolean()->required(),
            'website' => $schema->string()->format('uri')->nullable(),
            'public_phone' => $schema->string()->nullable(),
            'social_profiles' => $schema->array()->items($schema->string()->format('uri'))->required(),
            'detected_services' => $schema->array()->items($schema->string())->required(),
            'signals' => $schema->array()->items($schema->string())->required(),
            'score' => $schema->integer()->min(0)->max(100)->required(),
            'confidence' => $schema->string()->enum(['low', 'medium', 'high'])->required(),
            'rationale' => $schema->string()->min(1)->required(),
            'suggested_angle' => $schema->string()->nullable(),
            'sources' => $schema->array()->min(1)->items($schema->object([
                'name' => $schema->string()->min(1)->required(),
                'url' => $schema->string()->format('uri')->required(),
                'excerpt' => $schema->string()->nullable(),
            ]))->required(),
        ];
    }
}
