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

class ProspectDiscoveryAgent implements Agent, HasStructuredOutput, HasTools
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<'INSTRUCTIONS'
You discover Spanish fitness businesses that may fit WildForce. Search only for businesses that match the campaign scope in the user prompt.

Use web search before returning candidates. Return only facts you can support with the source URLs in each candidate. Do not invent emails, personal data, team sizes, social accounts, business needs, or services. Do not return individuals' private contact details. Do not recommend contacting anyone.

Each candidate needs at least one source. Score ICP fit from 0 to 100 using only supported signals. The rationale and every signal must be concise, factual, and tied to the evidence. Return fewer candidates rather than weak or duplicate candidates.
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
            'candidates' => $schema->array()
                ->items($schema->object([
                    'business_name' => $schema->string()->min(1)->required(),
                    'segment' => $schema->string()->enum(['coach', 'boutique_gym', 'gym'])->required(),
                    'website' => $schema->string()->format('uri')->nullable(),
                    'city' => $schema->string()->nullable(),
                    'province' => $schema->string()->nullable(),
                    'country' => $schema->string()->enum(['ES'])->required(),
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
                ]))->required(),
        ];
    }
}
