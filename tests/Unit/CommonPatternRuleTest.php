<?php

namespace CmsOrbit\PasswordSecurity\Tests\Unit;

use CmsOrbit\PasswordSecurity\Tests\TestCase;
use CmsOrbit\PasswordSecurity\Validators\Rules\CommonPatternRule;

class CommonPatternRuleTest extends TestCase
{
    protected CommonPatternRule $rule;
    protected array $config;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->rule = new CommonPatternRule();
        $this->config = [
            'enabled' => true,
            'block_sequential_numbers' => true,
            'block_sequential_letters' => true,
            'block_repeated_characters' => true,
            'block_keyboard_patterns' => true,
            'block_common_words' => true,
            'block_birthday_patterns' => true,
            'block_phone_patterns' => true,
            'sequential_threshold' => 4,
            'repeated_char_threshold' => 3,
            'common_words_list' => ['password', 'admin', '1234'],
            'keyboard_patterns' => ['qwerty', 'asdfgh'],
        ];
    }

    /** @test */
    public function it_blocks_sequential_numbers()
    {
        $this->assertFalse($this->rule->validate('pass1234word', $this->config));
        $this->assertFalse($this->rule->validate('test5678test', $this->config));
        $this->assertFalse($this->rule->validate('abc98765def', $this->config));
    }

    /** @test */
    public function it_blocks_sequential_letters()
    {
        $this->assertFalse($this->rule->validate('testabcd123', $this->config));
        $this->assertFalse($this->rule->validate('pass WXYZ test', $this->config));
    }

    /** @test */
    public function it_blocks_repeated_characters()
    {
        $this->assertFalse($this->rule->validate('paaassword', $this->config));
        $this->assertFalse($this->rule->validate('test1111test', $this->config));
    }

    /** @test */
    public function it_blocks_keyboard_patterns()
    {
        $this->assertFalse($this->rule->validate('testqwerty123', $this->config));
        $this->assertFalse($this->rule->validate('myasdfgh456', $this->config));
    }

    /** @test */
    public function it_blocks_common_words()
    {
        $this->assertFalse($this->rule->validate('mypassword123', $this->config));
        $this->assertFalse($this->rule->validate('admin@2024', $this->config));
        $this->assertFalse($this->rule->validate('test1234test', $this->config));
    }

    /** @test */
    public function it_blocks_birthday_patterns()
    {
        $this->assertFalse($this->rule->validate('user19900101', $this->config));
        $this->assertFalse($this->rule->validate('test20001225', $this->config));
    }

    /** @test */
    public function it_blocks_phone_patterns()
    {
        $this->assertFalse($this->rule->validate('pass01012345678', $this->config));
        $this->assertFalse($this->rule->validate('test0212345678', $this->config));
    }

    /** @test */
    public function it_passes_with_safe_password()
    {
        $this->assertTrue($this->rule->validate('MyS3cur3P@ss!', $this->config));
        $this->assertTrue($this->rule->validate('C0mpl3x#Pass', $this->config));
    }
}

