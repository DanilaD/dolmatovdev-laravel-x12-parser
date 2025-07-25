<?php

namespace DolmatovDev\X12Parser\Tests;

use DolmatovDev\X12Parser\Builder\X12Builder;
use DolmatovDev\X12Parser\DTO\Eligibility270DTO;
use PHPUnit\Framework\TestCase;

class X12BuilderTest extends TestCase
{
    private X12Builder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = new X12Builder();
    }

    public function test_build_from_270_dto()
    {
        $dto = new Eligibility270DTO(
            subscriberId: '987654321',
            subscriberFirstName: 'JOHN',
            subscriberLastName: 'DOE',
            subscriberDateOfBirth: '19800101',
            inquiries: [['service_type_code' => '30']]
        );

        $result = $this->builder->buildFrom270DTO($dto);

        $this->assertIsString($result);
        $this->assertStringContainsString('ISA*', $result);
        $this->assertStringContainsString('GS*HS*', $result);
        $this->assertStringContainsString('ST*270*', $result);
        $this->assertStringContainsString('NM1*IL*1*DOE*JOHN****MI*987654321', $result);
        $this->assertStringContainsString('DMG*D8*19800101', $result);
        $this->assertStringContainsString('EQ*30', $result);
        $this->assertStringContainsString('SE*', $result);
        $this->assertStringContainsString('GE*', $result);
        $this->assertStringContainsString('IEA*', $result);
    }

    public function test_build_with_custom_delimiters()
    {
        $dto = new Eligibility270DTO(
            subscriberId: '987654321',
            subscriberFirstName: 'JOHN',
            subscriberLastName: 'DOE',
            inquiries: [['service_type_code' => '30']]
        );

        $this->builder->setDelimiters([
            'segment' => '~',
            'element' => '^',
            'sub_element' => '>'
        ]);

        $result = $this->builder->buildFrom270DTO($dto);

        $this->assertIsString($result);
        $this->assertStringContainsString('ISA^', $result);
        $this->assertStringContainsString('ST^270^', $result);
        $this->assertStringContainsString('NM1^IL^1^DOE^JOHN^^^^MI^987654321', $result);
    }

    public function test_get_default_delimiters()
    {
        $delimiters = $this->builder->getDefaultDelimiters();

        $this->assertEquals([
            'segment' => '~',
            'element' => '*',
            'sub_element' => '>'
        ], $delimiters);
    }

    public function test_set_delimiters_for_transaction()
    {
        $this->builder->setDelimitersForTransaction('270');

        $delimiters = $this->builder->getDelimiters();
        
        // Should use default delimiters if no config is available
        $this->assertEquals([
            'segment' => '~',
            'element' => '*',
            'sub_element' => '>'
        ], $delimiters);
    }

    public function test_build_with_minimal_dto()
    {
        $dto = new Eligibility270DTO(
            subscriberId: '987654321',
            subscriberFirstName: 'JOHN',
            subscriberLastName: 'DOE',
            inquiries: [['service_type_code' => '30']]
        );

        $result = $this->builder->buildFrom270DTO($dto);

        $this->assertIsString($result);
        $this->assertIsString($result);
        $this->assertStringContainsString('ISA*', $result);
        $this->assertStringContainsString('ST*270*', $result);
        $this->assertStringContainsString('NM1*IL*1*DOE*JOHN****MI*987654321', $result);
    }
} 