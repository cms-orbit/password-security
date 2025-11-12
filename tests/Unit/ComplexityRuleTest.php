<?php

namespace CmsOrbit\PasswordSecurity\Tests\Unit;

use CmsOrbit\PasswordSecurity\Tests\TestCase;
use CmsOrbit\PasswordSecurity\Validators\Rules\ComplexityRule;

class ComplexityRuleTest extends TestCase
{
    protected ComplexityRule $rule;
    protected array $config;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->rule = new ComplexityRule();
        $this->config = [
            'enabled' => true,
            'min_length_2_types' => 10,
            'min_length_3_types' => 8,
            'min_length_4_types' => 8,
            'special_characters' => '!@#$%^&*()_+-=[]{}|;:,.<>?~`',
        ];
    }

    /** @test */
    public function it_passes_with_2_types_and_10_characters()
    {
        $this->assertTrue($this->rule->validate('abcdefgh12', $this->config));
        $this->assertTrue($this->rule->validate('ABCDEFGH12', $this->config));
    }

    /** @test */
    public function it_fails_with_2_types_and_less_than_10_characters()
    {
        $this->assertFalse($this->rule->validate('abcdef12', $this->config));
        $this->assertFalse($this->rule->validate('ABC123', $this->config));
    }

    /** @test */
    public function it_passes_with_3_types_and_8_characters()
    {
        $this->assertTrue($this->rule->validate('Abcd1234', $this->config));
        $this->assertTrue($this->rule->validate('Test123!', $this->config));
    }

    /** @test */
    public function it_fails_with_3_types_and_less_than_8_characters()
    {
        $this->assertFalse($this->rule->validate('Abc123', $this->config));
        $this->assertFalse($this->rule->validate('Te12!', $this->config));
    }

    /** @test */
    public function it_passes_with_4_types()
    {
        $this->assertTrue($this->rule->validate('Abcd123!', $this->config));
        $this->assertTrue($this->rule->validate('Test@123', $this->config));
    }

    /** @test */
    public function it_fails_with_only_1_type()
    {
        $this->assertFalse($this->rule->validate('abcdefghijk', $this->config));
        $this->assertFalse($this->rule->validate('ABCDEFGHIJK', $this->config));
        $this->assertFalse($this->rule->validate('12345678901', $this->config));
    }

    /** @test */
    public function it_respects_required_types()
    {
        $config = array_merge($this->config, [
            'require_uppercase' => true,
            'require_lowercase' => true,
            'require_numbers' => true,
            'require_special' => true,
        ]);

        $this->assertTrue($this->rule->validate('Abc123!@#', $config));
        $this->assertFalse($this->rule->validate('abc123!@#', $config)); // no uppercase
        $this->assertFalse($this->rule->validate('ABC123!@#', $config)); // no lowercase
        $this->assertFalse($this->rule->validate('Abcdef!@#', $config)); // no numbers
        $this->assertFalse($this->rule->validate('Abc123456', $config)); // no special
    }
}

