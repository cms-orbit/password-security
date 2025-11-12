<?php

namespace CmsOrbit\PasswordSecurity\Tests\Feature;

use CmsOrbit\PasswordSecurity\Exceptions\WeakPasswordException;
use CmsOrbit\PasswordSecurity\Tests\TestCase;
use CmsOrbit\PasswordSecurity\Validators\PasswordValidator;

class PasswordValidatorTest extends TestCase
{
    protected PasswordValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->validator = new PasswordValidator();
        
        config([
            'password-security.enabled' => true,
            'password-security.complexity.enabled' => true,
            'password-security.common_patterns.enabled' => true,
            'password-security.personal_info.enabled' => true,
            'password-security.history.enabled' => true,
        ]);
    }

    /** @test */
    public function it_validates_strong_password()
    {
        $this->assertTrue($this->validator->validate('MyS3cur3P@ssw0rd!'));
    }

    /** @test */
    public function it_throws_exception_for_weak_password()
    {
        $this->expectException(WeakPasswordException::class);
        
        $this->validator->validate('weak');
    }

    /** @test */
    public function it_returns_false_without_throwing_exception()
    {
        config(['password-security.exceptions.throw_exceptions' => false]);
        
        $result = $this->validator->validate('weak', null, false);
        
        $this->assertFalse($result);
        $this->assertNotEmpty($this->validator->getErrors());
    }

    /** @test */
    public function it_validates_complexity()
    {
        $this->assertTrue($this->validator->validateComplexity('Abcdefgh12'));
        $this->assertFalse($this->validator->validateComplexity('weak'));
    }

    /** @test */
    public function it_validates_common_patterns()
    {
        $this->assertTrue($this->validator->validateCommonPattern('MyS3cur3P@ss'));
        $this->assertFalse($this->validator->validateCommonPattern('password123'));
    }
}

