<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\Editor\Conditions\EditorConditionsAttributeNormalizer;
use Voodflow\Voodbuilder\Tests\TestCase;

class EditorConditionsAttributeNormalizerTest extends TestCase
{
    public function test_normalizes_broken_editor_condition_attributes(): void
    {
        $encoded = EditorConditionsAttributeNormalizer::encodeDefinition([
            'match' => 'any',
            'sets' => [
                ['conditions' => [['key' => 'locale', 'compare' => '==', 'value' => 'it']]],
            ],
        ]);

        $broken = '<div data-voodbuilder-conditions="'.$encoded.'" key="locale" compare="==" value="it" class="hero">Secret</div>';

        $normalized = EditorConditionsAttributeNormalizer::normalize($broken);

        $this->assertStringNotContainsString('key="locale"', $normalized);
        $this->assertStringContainsString('data-voodbuilder-conditions=', $normalized);
        $this->assertStringContainsString('class="hero"', $normalized);

        preg_match('/data-voodbuilder-conditions="([^"]+)"/', $normalized, $matches);

        $definition = EditorConditionsAttributeNormalizer::parseDefinition($matches[1] ?? '');

        $this->assertSame('any', $definition['match'] ?? null);
        $this->assertSame('locale', $definition['sets'][0]['conditions'][0]['key'] ?? null);
    }

    public function test_encodes_entity_safe_condition_payload(): void
    {
        $definition = [
            'match' => 'any',
            'sets' => [
                ['conditions' => [['key' => 'user_logged_in', 'compare' => '==', 'value' => '1']]],
            ],
        ];

        $encoded = EditorConditionsAttributeNormalizer::encodeDefinition($definition);
        $html = '<section data-voodbuilder-conditions="'.$encoded.'">Content</section>';

        $normalized = EditorConditionsAttributeNormalizer::normalize($html);

        preg_match('/data-voodbuilder-conditions="([^"]+)"/', $normalized, $matches);

        $parsed = EditorConditionsAttributeNormalizer::parseDefinition($matches[1] ?? '');

        $this->assertSame('1', $parsed['sets'][0]['conditions'][0]['value'] ?? null);
    }
}
