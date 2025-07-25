<?php

namespace DolmatovDev\X12Parser\Tests;

use DolmatovDev\X12Parser\Validators\Validator270;
use DolmatovDev\X12Parser\DTO\ValidationResult;
use DolmatovDev\X12Parser\DTO\Eligibility270DTO;
use PHPUnit\Framework\TestCase;

class Validator270Test extends TestCase
{
    private Validator270 $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new Validator270();
    }

    public function test_validate_valid_270_structure()
    {
        $segments = [
            'ISA*00* *00* *ZZ*SENDER *ZZ*RECEIVER *230101*1200*U*00401*000000001*0*P',
            'GS*HS*SENDER*RECEIVER*20230101*1200*1*X*005010X279A1',
            'ST*270*0001*005010X279A1',
            'BHT*0022*13*10001234*20230101*1200',
            'HL*1**20*1',
            'NM1*PR*2*PAYER NAME*****PI*123456789',
            'HL*2*1*21*1',
            'NM1*P3*2*PROVIDER NAME*****SV*987654321',
            'HL*3*2*22*0',
            'TRN*1*123456789*987654321',
            'NM1*IL*1*DOE*JOHN****MI*12345678901',
            'DMG*D8*19800101',
            'DTP*291*D8*20230101',
            'EQ*30',
            'SE*13*0001',
            'GE*1*1',
            'IEA*1*000000001'
        ];

        $result = $this->validator->validate($segments);



        $this->assertInstanceOf(ValidationResult::class, $result);
        $this->assertTrue($result->isSuccessful());
        $this->assertEmpty($result->errors);
    }

    public function test_validate_missing_isa_segment()
    {
        $segments = [
            'GS*HS*SENDER*RECEIVER*20230101*1200*1*X*005010X279A1',
            'ST*270*0001*005010X279A1'
        ];

        $result = $this->validator->validate($segments);

        $this->assertInstanceOf(ValidationResult::class, $result);
        $this->assertFalse($result->isSuccessful());
        $this->assertNotEmpty($result->errors);
        $this->assertStringContainsString('ISA', implode(' ', $result->errors));
    }

    public function test_validate_missing_st_segment()
    {
        $segments = [
            'ISA*00*          *00*          *ZZ*SENDER         *ZZ*RECEIVER       *230101*1200*U*00401*000000001*0*P*>',
            'GS*HS*SENDER*RECEIVER*20230101*1200*1*X*005010X279A1'
        ];

        $result = $this->validator->validate($segments);

        $this->assertInstanceOf(ValidationResult::class, $result);
        $this->assertFalse($result->isSuccessful());
        $this->assertNotEmpty($result->errors);
        $this->assertStringContainsString('ST', implode(' ', $result->errors));
    }

    public function test_validate_invalid_isa_segment()
    {
        $segments = [
            'ISA*00*          *00*          *ZZ*SENDER         *ZZ*RECEIVER       *230101*1200*U*00401*000000001*0*P*',
            'ST*270*0001*005010X279A1'
        ];

        $result = $this->validator->validate($segments);

        $this->assertInstanceOf(ValidationResult::class, $result);
        $this->assertFalse($result->isSuccessful());
        $this->assertNotEmpty($result->errors);
    }

    public function test_validate_wrong_transaction_type()
    {
        $segments = [
            'ISA*00*          *00*          *ZZ*SENDER         *ZZ*RECEIVER       *230101*1200*U*00401*000000001*0*P*>',
            'ST*837*0001*005010X222A1'
        ];

        $result = $this->validator->validate($segments);

        $this->assertInstanceOf(ValidationResult::class, $result);
        $this->assertFalse($result->isSuccessful());
        $this->assertNotEmpty($result->errors);
        $this->assertStringContainsString('270', implode(' ', $result->errors));
    }

    public function test_get_transaction_type()
    {
        $transactionType = $this->validator->getTransactionType();

        $this->assertEquals('270', $transactionType);
    }

    public function test_get_required_segments()
    {
        $requiredSegments = $this->validator->getRequiredSegments();

        $this->assertContains('ISA', $requiredSegments);
        $this->assertContains('GS', $requiredSegments);
        $this->assertContains('ST', $requiredSegments);
        $this->assertContains('BHT', $requiredSegments);
        $this->assertContains('HL', $requiredSegments);
        $this->assertContains('NM1', $requiredSegments);
        $this->assertContains('SE', $requiredSegments);
        $this->assertContains('GE', $requiredSegments);
        $this->assertContains('IEA', $requiredSegments);
    }

    public function test_get_optional_segments()
    {
        $optionalSegments = $this->validator->getOptionalSegments();

        $this->assertContains('TRN', $optionalSegments);
        $this->assertContains('DMG', $optionalSegments);
        $this->assertContains('DTP', $optionalSegments);
        $this->assertContains('EQ', $optionalSegments);
        $this->assertContains('QTY', $optionalSegments);
    }

    public function test_validate_empty_segments()
    {
        $result = $this->validator->validate([]);

        $this->assertInstanceOf(ValidationResult::class, $result);
        $this->assertFalse($result->isSuccessful());
        $this->assertNotEmpty($result->errors);
    }
} 