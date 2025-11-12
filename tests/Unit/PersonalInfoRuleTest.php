<?php

namespace CmsOrbit\PasswordSecurity\Tests\Unit;

use CmsOrbit\PasswordSecurity\Tests\TestCase;
use CmsOrbit\PasswordSecurity\Validators\Rules\PersonalInfoRule;
use Illuminate\Database\Eloquent\Model;

class PersonalInfoRuleTest extends TestCase
{
    protected PersonalInfoRule $rule;
    protected array $config;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->rule = new PersonalInfoRule();
        $this->config = [
            'enabled' => true,
            'block_name' => true,
            'block_email' => true,
            'block_username' => true,
            'min_substring_length' => 3,
            'case_insensitive' => true,
        ];
    }

    /** @test */
    public function it_blocks_password_containing_name()
    {
        $user = $this->createMockUser([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $this->assertFalse($this->rule->validate('MyJohn123', $user, $this->config));
        $this->assertFalse($this->rule->validate('DoePassword!', $user, $this->config));
    }

    /** @test */
    public function it_blocks_password_containing_email()
    {
        $user = $this->createMockUser([
            'name' => 'Test User',
            'email' => 'testuser@example.com',
        ]);

        $this->assertFalse($this->rule->validate('testuser123!', $user, $this->config));
    }

    /** @test */
    public function it_passes_with_short_substrings()
    {
        $user = $this->createMockUser([
            'name' => 'Jo',
            'email' => 'jo@example.com',
        ]);

        // 'jo'는 3자 미만이므로 허용
        $this->assertTrue($this->rule->validate('MyJo123!', $user, $this->config));
    }

    /** @test */
    public function it_passes_with_safe_password()
    {
        $user = $this->createMockUser([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $this->assertTrue($this->rule->validate('MyS3cur3P@ss!', $user, $this->config));
        $this->assertTrue($this->rule->validate('C0mpl3x#W0rd', $user, $this->config));
    }

    protected function createMockUser(array $attributes): object
    {
        return new class($attributes) {
            protected array $attributes;

            public function __construct(array $attributes)
            {
                $this->attributes = $attributes;
            }

            public function __get($key)
            {
                return $this->attributes[$key] ?? null;
            }

            public function getPersonalInfoFields(): array
            {
                return [
                    'name' => 'name',
                    'email' => 'email',
                ];
            }
        };
    }
}

