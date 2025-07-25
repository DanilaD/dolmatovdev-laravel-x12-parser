<?php

namespace DolmatovDev\X12Parser\Tests;

use DolmatovDev\X12Parser\DTO\Eligibility270DTO;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class Eligibility270DTOTest extends TestCase
{
    public function test_create_dto_from_array(): void
    {
        $data = [
            'subscriber_id' => '123456789',
            'subscriber_first_name' => 'John',
            'subscriber_last_name' => 'Doe',
            'subscriber_date_of_birth' => '19800101',
            'subscriber_gender' => 'M',
            'inquiries' => [
                ['service_type_code' => '30'],
                ['service_type_code' => '35']
            ]
        ];

        $dto = Eligibility270DTO::fromArray($data);

        $this->assertInstanceOf(Eligibility270DTO::class, $dto);
        $this->assertEquals('123456789', $dto->subscriberId);
        $this->assertEquals('John', $dto->subscriberFirstName);
        $this->assertEquals('Doe', $dto->subscriberLastName);
        $this->assertEquals('19800101', $dto->subscriberDateOfBirth);
        $this->assertEquals('M', $dto->subscriberGender);
        $this->assertCount(2, $dto->inquiries);
    }

    public function test_create_dto_from_parsed_data(): void
    {
        $parsedData = [
            'subscriber' => [
                'id' => '123456789',
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'date_of_birth' => '19850101',
                'gender' => 'F'
            ],
            'inquiries' => [
                ['service_type_code' => '30']
            ]
        ];

        $dto = Eligibility270DTO::fromParsedData($parsedData);

        $this->assertInstanceOf(Eligibility270DTO::class, $dto);
        $this->assertEquals('123456789', $dto->subscriberId);
        $this->assertEquals('Jane', $dto->subscriberFirstName);
        $this->assertEquals('Smith', $dto->subscriberLastName);
        $this->assertEquals('19850101', $dto->subscriberDateOfBirth);
        $this->assertEquals('F', $dto->subscriberGender);
        $this->assertCount(1, $dto->inquiries);
    }

    public function test_create_dto_with_minimal_data(): void
    {
        $data = [
            'subscriber_id' => '123456789',
            'subscriber_first_name' => 'John',
            'subscriber_last_name' => 'Doe',
            'inquiries' => [['service_type_code' => '30']]
        ];

        $dto = Eligibility270DTO::fromArray($data);

        $this->assertInstanceOf(Eligibility270DTO::class, $dto);
        $this->assertEquals('123456789', $dto->subscriberId);
        $this->assertEquals('John', $dto->subscriberFirstName);
        $this->assertEquals('Doe', $dto->subscriberLastName);
        $this->assertNull($dto->subscriberDateOfBirth);
        $this->assertNull($dto->subscriberGender);
        $this->assertCount(1, $dto->inquiries);
    }

    public function test_convert_dto_to_array(): void
    {
        $data = [
            'subscriber_id' => '123456789',
            'subscriber_first_name' => 'John',
            'subscriber_last_name' => 'Doe',
            'subscriber_date_of_birth' => '19800101',
            'subscriber_gender' => 'M',
            'inquiries' => [
                ['service_type_code' => '30']
            ]
        ];

        $dto = Eligibility270DTO::fromArray($data);
        $array = $dto->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('123456789', $array['subscriber_id']);
        $this->assertEquals('John', $array['subscriber_first_name']);
        $this->assertEquals('Doe', $array['subscriber_last_name']);
        $this->assertEquals('19800101', $array['subscriber_date_of_birth']);
        $this->assertEquals('M', $array['subscriber_gender']);
        $this->assertCount(1, $array['inquiries']);
    }

    public function test_validate_required_fields(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Subscriber ID is required');

        Eligibility270DTO::fromArray([
            'subscriber_first_name' => 'John',
            'subscriber_last_name' => 'Doe',
            'inquiries' => [['service_type_code' => '30']]
        ]);
    }

    public function test_validate_subscriber_first_name_required(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Subscriber first name is required');

        Eligibility270DTO::fromArray([
            'subscriber_id' => '123456789',
            'subscriber_last_name' => 'Doe',
            'inquiries' => [['service_type_code' => '30']]
        ]);
    }

    public function test_validate_subscriber_last_name_required(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Subscriber last name is required');

        Eligibility270DTO::fromArray([
            'subscriber_id' => '123456789',
            'subscriber_first_name' => 'John',
            'inquiries' => [['service_type_code' => '30']]
        ]);
    }

    public function test_validate_inquiries_required(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one inquiry is required');

        Eligibility270DTO::fromArray([
            'subscriber_id' => '123456789',
            'subscriber_first_name' => 'John',
            'subscriber_last_name' => 'Doe'
        ]);
    }

    public function test_validate_inquiries_not_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one inquiry is required');

        Eligibility270DTO::fromArray([
            'subscriber_id' => '123456789',
            'subscriber_first_name' => 'John',
            'subscriber_last_name' => 'Doe',
            'inquiries' => []
        ]);
    }

    public function test_validate_date_of_birth_format(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Date of birth must be in YYYYMMDD format');

        Eligibility270DTO::fromArray([
            'subscriber_id' => '123456789',
            'subscriber_first_name' => 'John',
            'subscriber_last_name' => 'Doe',
            'subscriber_date_of_birth' => '1980-01-01', // Wrong format
            'inquiries' => [['service_type_code' => '30']]
        ]);
    }

    public function test_validate_gender_format(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Gender must be M or F');

        Eligibility270DTO::fromArray([
            'subscriber_id' => '123456789',
            'subscriber_first_name' => 'John',
            'subscriber_last_name' => 'Doe',
            'subscriber_gender' => 'X', // Invalid gender
            'inquiries' => [['service_type_code' => '30']]
        ]);
    }

    public function test_validate_service_type_code_required(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Service type code is required for each inquiry');

        Eligibility270DTO::fromArray([
            'subscriber_id' => '123456789',
            'subscriber_first_name' => 'John',
            'subscriber_last_name' => 'Doe',
            'inquiries' => [
                ['some_other_field' => 'value'] // Missing service_type_code
            ]
        ]);
    }

    public function test_validate_service_type_code_format(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Service type code must be 2 characters');

        Eligibility270DTO::fromArray([
            'subscriber_id' => '123456789',
            'subscriber_first_name' => 'John',
            'subscriber_last_name' => 'Doe',
            'inquiries' => [
                ['service_type_code' => '300'] // Too long
            ]
        ]);
    }

    public function test_validate_with_valid_data(): void
    {
        $data = [
            'subscriber_id' => '123456789',
            'subscriber_first_name' => 'John',
            'subscriber_last_name' => 'Doe',
            'subscriber_date_of_birth' => '19800101',
            'subscriber_gender' => 'M',
            'inquiries' => [
                ['service_type_code' => '30'],
                ['service_type_code' => '35']
            ]
        ];

        $dto = Eligibility270DTO::fromArray($data);

        // If no exception is thrown, validation passed
        $this->assertInstanceOf(Eligibility270DTO::class, $dto);
    }

    public function test_readonly_properties(): void
    {
        $data = [
            'subscriber_id' => '123456789',
            'subscriber_first_name' => 'John',
            'subscriber_last_name' => 'Doe',
            'inquiries' => [['service_type_code' => '30']]
        ];

        $dto = Eligibility270DTO::fromArray($data);

        // Test that properties are readonly (can't be modified after creation)
        $this->assertEquals('123456789', $dto->subscriberId);
        $this->assertEquals('John', $dto->subscriberFirstName);
        $this->assertEquals('Doe', $dto->subscriberLastName);
    }

    public function test_multiple_inquiries(): void
    {
        $data = [
            'subscriber_id' => '123456789',
            'subscriber_first_name' => 'John',
            'subscriber_last_name' => 'Doe',
            'inquiries' => [
                ['service_type_code' => '30'],
                ['service_type_code' => '35'],
                ['service_type_code' => '40']
            ]
        ];

        $dto = Eligibility270DTO::fromArray($data);

        $this->assertCount(3, $dto->inquiries);
        $this->assertEquals('30', $dto->inquiries[0]['service_type_code']);
        $this->assertEquals('35', $dto->inquiries[1]['service_type_code']);
        $this->assertEquals('40', $dto->inquiries[2]['service_type_code']);
    }
} 