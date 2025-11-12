<?php

namespace CmsOrbit\PasswordSecurity\Tests\Unit;

use CmsOrbit\PasswordSecurity\Tests\TestCase;
use CmsOrbit\PasswordSecurity\Validators\Rules\HistoryRule;

class HistoryRuleTest extends TestCase
{
    protected HistoryRule $rule;
    protected array $config;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->rule = new HistoryRule();
        $this->config = [
            'enabled' => true,
            'check_last_n_passwords' => 3,
        ];
    }

    /** @test */
    public function it_passes_for_new_user()
    {
        $user = $this->createMockUser(false);
        
        $this->assertTrue($this->rule->validate('NewPassword123!', $user, $this->config));
    }

    /** @test */
    public function it_blocks_password_in_history()
    {
        $user = $this->createMockUser(true, true);
        
        $this->assertFalse($this->rule->validate('OldPassword123!', $user, $this->config));
    }

    /** @test */
    public function it_passes_password_not_in_history()
    {
        $user = $this->createMockUser(true, false);
        
        $this->assertTrue($this->rule->validate('NewUniquePass123!', $user, $this->config));
    }

    protected function createMockUser(bool $exists, bool $inHistory = false): object
    {
        return new class($exists, $inHistory) {
            public bool $exists;
            protected bool $inHistory;

            public function __construct(bool $exists, bool $inHistory)
            {
                $this->exists = $exists;
                $this->inHistory = $inHistory;
            }

            public function isPasswordInHistory(string $password): bool
            {
                return $this->inHistory;
            }
        };
    }
}

