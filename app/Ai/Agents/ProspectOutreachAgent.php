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

class ProspectOutreachAgent implements Agent, HasStructuredOutput, HasTools
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<'INSTRUCTIONS'
You prepare one careful first outreach draft for a qualified Spanish fitness business that may fit WildForce. Use web search before answering and only research the business in the prompt.

Choose the most appropriate public professional channel: email, contact_form, instagram, linkedin, or website. Return public professional emails only when directly supported by a source. Never invent a contact, email address, social account, pain point, or business fact. Do not return private personal data.

Write the message in the preferred_language supplied in the user prompt. Mention that WildForce is based in Manresa to create local proximity.

Include at most one WildForce link when it is useful: use https://www.wildforce.app/gym for a gym or boutique_gym, and https://www.wildforce.app/coach for a coach. Do not use tracking links or other product URLs. The message must be a draft for human review: no fabricated claims, no pressure, and no promise that it was sent. Include a concise rationale and sources for the chosen channel and personalization.

The user prompt includes approved value propositions for this prospect. Choose one or two that match the prospect's evidence, return their keys, and weave only those selected propositions naturally into the message. Never claim a capability outside that list. End with a light invitation to discuss other relevant WildForce capabilities, without listing them all.
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
            'channel' => $schema->string()->enum(['email', 'contact_form', 'instagram', 'linkedin', 'website'])->required(),
            'channel_url' => $schema->string()->format('uri')->nullable(),
            'public_email' => $schema->string()->format('email')->nullable(),
            'recipient_name' => $schema->string()->nullable(),
            'recipient_role' => $schema->string()->nullable(),
            'language' => $schema->string()->enum(['ca', 'es'])->required(),
            'subject' => $schema->string()->nullable(),
            'message' => $schema->string()->min(1)->required(),
            'rationale' => $schema->string()->min(1)->required(),
            'selected_value_proposition_keys' => $schema->array()->max(2)->items($schema->string())->required(),
            'sources' => $schema->array()->min(1)->items($schema->object([
                'name' => $schema->string()->min(1)->required(),
                'url' => $schema->string()->format('uri')->required(),
                'excerpt' => $schema->string()->nullable(),
            ]))->required(),
        ];
    }
}
