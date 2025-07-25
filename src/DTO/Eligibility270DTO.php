<?php

namespace DolmatovDev\X12Parser\DTO;

use InvalidArgumentException;

/**
 * Data Transfer Object for ANSI X12 270 (Eligibility/Benefit Inquiry) data.
 * 
 * This DTO provides a structured representation of 270 transaction data
 * with validation and immutability guarantees.
 */
class Eligibility270DTO
{
    /**
     * Create a new Eligibility270DTO instance.
     * 
     * @param string $subscriberId Subscriber's unique identifier
     * @param string $subscriberFirstName Subscriber's first name
     * @param string $subscriberLastName Subscriber's last name
     * @param string|null $subscriberMiddleName Subscriber's middle name
     * @param string|null $subscriberDateOfBirth Subscriber's date of birth (YYYYMMDD)
     * @param string|null $subscriberGender Subscriber's gender (M/F)
     * @param string|null $subscriberAddress Subscriber's address
     * @param string|null $subscriberCity Subscriber's city
     * @param string|null $subscriberState Subscriber's state
     * @param string|null $subscriberZip Subscriber's zip code
     * @param string|null $subscriberGroupNumber Subscriber's group number
     * @param string|null $subscriberMemberId Subscriber's member ID
     * @param array $inquiries Array of benefit inquiries
     * @param array $interchangeData Interchange control data
     * @param array $functionalGroupData Functional group data
     * @param array $transactionData Transaction set data
     */
    public function __construct(
        public readonly string $subscriberId,
        public readonly string $subscriberFirstName,
        public readonly string $subscriberLastName,
        public readonly ?string $subscriberMiddleName = null,
        public readonly ?string $subscriberDateOfBirth = null,
        public readonly ?string $subscriberGender = null,
        public readonly ?string $subscriberAddress = null,
        public readonly ?string $subscriberCity = null,
        public readonly ?string $subscriberState = null,
        public readonly ?string $subscriberZip = null,
        public readonly ?string $subscriberGroupNumber = null,
        public readonly ?string $subscriberMemberId = null,
        public readonly array $inquiries = [],
        public readonly array $interchangeData = [],
        public readonly array $functionalGroupData = [],
        public readonly array $transactionData = []
    ) {
        $this->validate();
    }

    /**
     * Create an Eligibility270DTO from parsed ANSI data.
     * 
     * @param array $data Parsed ANSI data
     * @return self New DTO instance
     */
    public static function fromParsedData(array $data): self
    {
        $subscriber = $data['subscriber'] ?? [];
        $interchange = $data['interchange'] ?? [];
        $functionalGroup = $data['functional_group'] ?? [];
        $transaction = $data['transaction'] ?? [];

        return new self(
            subscriberId: $subscriber['id'] ?? '',
            subscriberFirstName: $subscriber['first_name'] ?? '',
            subscriberLastName: $subscriber['last_name'] ?? '',
            subscriberMiddleName: $subscriber['middle_name'] ?? null,
            subscriberDateOfBirth: $subscriber['date_of_birth'] ?? null,
            subscriberGender: $subscriber['gender'] ?? null,
            subscriberAddress: $subscriber['address'] ?? null,
            subscriberCity: $subscriber['city'] ?? null,
            subscriberState: $subscriber['state'] ?? null,
            subscriberZip: $subscriber['zip'] ?? null,
            subscriberGroupNumber: $subscriber['group_number'] ?? null,
            subscriberMemberId: $subscriber['member_id'] ?? null,
            inquiries: $data['inquiries'] ?? [],
            interchangeData: $interchange,
            functionalGroupData: $functionalGroup,
            transactionData: $transaction
        );
    }

    /**
     * Create an Eligibility270DTO from array data.
     * 
     * @param array $data Array data
     * @return self New DTO instance
     */
    public static function fromArray(array $data): self
    {
        return new self(
            subscriberId: $data['subscriber_id'] ?? '',
            subscriberFirstName: $data['subscriber_first_name'] ?? '',
            subscriberLastName: $data['subscriber_last_name'] ?? '',
            subscriberMiddleName: $data['subscriber_middle_name'] ?? null,
            subscriberDateOfBirth: $data['subscriber_date_of_birth'] ?? null,
            subscriberGender: $data['subscriber_gender'] ?? null,
            subscriberAddress: $data['subscriber_address'] ?? null,
            subscriberCity: $data['subscriber_city'] ?? null,
            subscriberState: $data['subscriber_state'] ?? null,
            subscriberZip: $data['subscriber_zip'] ?? null,
            subscriberGroupNumber: $data['subscriber_group_number'] ?? null,
            subscriberMemberId: $data['subscriber_member_id'] ?? null,
            inquiries: $data['inquiries'] ?? [],
            interchangeData: $data['interchange_data'] ?? [],
            functionalGroupData: $data['functional_group_data'] ?? [],
            transactionData: $data['transaction_data'] ?? []
        );
    }

    /**
     * Convert DTO to array.
     * 
     * @return array Array representation
     */
    public function toArray(): array
    {
        return [
            'subscriber_id' => $this->subscriberId,
            'subscriber_first_name' => $this->subscriberFirstName,
            'subscriber_last_name' => $this->subscriberLastName,
            'subscriber_middle_name' => $this->subscriberMiddleName,
            'subscriber_date_of_birth' => $this->subscriberDateOfBirth,
            'subscriber_gender' => $this->subscriberGender,
            'subscriber_address' => $this->subscriberAddress,
            'subscriber_city' => $this->subscriberCity,
            'subscriber_state' => $this->subscriberState,
            'subscriber_zip' => $this->subscriberZip,
            'subscriber_group_number' => $this->subscriberGroupNumber,
            'subscriber_member_id' => $this->subscriberMemberId,
            'inquiries' => $this->inquiries,
            'interchange_data' => $this->interchangeData,
            'functional_group_data' => $this->functionalGroupData,
            'transaction_data' => $this->transactionData,
        ];
    }

    /**
     * Get subscriber's full name.
     * 
     * @return string Full name
     */
    public function getSubscriberFullName(): string
    {
        $name = $this->subscriberFirstName;
        
        if ($this->subscriberMiddleName) {
            $name .= ' ' . $this->subscriberMiddleName;
        }
        
        $name .= ' ' . $this->subscriberLastName;
        
        return trim($name);
    }

    /**
     * Get subscriber's address as a single string.
     * 
     * @return string|null Full address
     */
    public function getSubscriberFullAddress(): ?string
    {
        if (!$this->subscriberAddress) {
            return null;
        }

        $address = $this->subscriberAddress;
        
        if ($this->subscriberCity) {
            $address .= ', ' . $this->subscriberCity;
        }
        
        if ($this->subscriberState) {
            $address .= ', ' . $this->subscriberState;
        }
        
        if ($this->subscriberZip) {
            $address .= ' ' . $this->subscriberZip;
        }
        
        return $address;
    }

    /**
     * Check if subscriber has complete address information.
     * 
     * @return bool True if complete address
     */
    public function hasCompleteAddress(): bool
    {
        return !empty($this->subscriberAddress) &&
               !empty($this->subscriberCity) &&
               !empty($this->subscriberState) &&
               !empty($this->subscriberZip);
    }

    /**
     * Check if subscriber has demographic information.
     * 
     * @return bool True if has demographics
     */
    public function hasDemographics(): bool
    {
        return !empty($this->subscriberDateOfBirth) || !empty($this->subscriberGender);
    }

    /**
     * Create a new instance with updated subscriber ID.
     * 
     * @param string $subscriberId New subscriber ID
     * @return self New instance
     */
    public function withSubscriberId(string $subscriberId): self
    {
        return new self(
            $subscriberId,
            $this->subscriberFirstName,
            $this->subscriberLastName,
            $this->subscriberMiddleName,
            $this->subscriberDateOfBirth,
            $this->subscriberGender,
            $this->subscriberAddress,
            $this->subscriberCity,
            $this->subscriberState,
            $this->subscriberZip,
            $this->subscriberGroupNumber,
            $this->subscriberMemberId,
            $this->inquiries,
            $this->interchangeData,
            $this->functionalGroupData,
            $this->transactionData
        );
    }

    /**
     * Create a new instance with updated subscriber name.
     * 
     * @param string $firstName New first name
     * @param string $lastName New last name
     * @param string|null $middleName New middle name
     * @return self New instance
     */
    public function withSubscriberName(string $firstName, string $lastName, ?string $middleName = null): self
    {
        return new self(
            $this->subscriberId,
            $firstName,
            $lastName,
            $middleName,
            $this->subscriberDateOfBirth,
            $this->subscriberGender,
            $this->subscriberAddress,
            $this->subscriberCity,
            $this->subscriberState,
            $this->subscriberZip,
            $this->subscriberGroupNumber,
            $this->subscriberMemberId,
            $this->inquiries,
            $this->interchangeData,
            $this->functionalGroupData,
            $this->transactionData
        );
    }

    /**
     * Validate the DTO data.
     * 
     * @throws InvalidArgumentException
     */
    public function validate(): void
    {
        if (empty($this->subscriberId)) {
            throw new InvalidArgumentException('Subscriber ID is required');
        }

        if (empty($this->subscriberFirstName)) {
            throw new InvalidArgumentException('Subscriber first name is required');
        }

        if (empty($this->subscriberLastName)) {
            throw new InvalidArgumentException('Subscriber last name is required');
        }

        if ($this->subscriberDateOfBirth && !preg_match('/^\d{8}$/', $this->subscriberDateOfBirth)) {
            throw new InvalidArgumentException('Date of birth must be in YYYYMMDD format');
        }

        if ($this->subscriberGender && !in_array(strtoupper($this->subscriberGender), ['M', 'F'])) {
            throw new InvalidArgumentException('Gender must be M or F');
        }

        if ($this->subscriberState && !preg_match('/^[A-Z]{2}$/', strtoupper($this->subscriberState))) {
            throw new InvalidArgumentException('Subscriber state must be a 2-letter code');
        }

        if ($this->subscriberZip && !preg_match('/^\d{5}(-\d{4})?$/', $this->subscriberZip)) {
            throw new InvalidArgumentException('Subscriber zip code must be in 12345 or 12345-6789 format');
        }

        // Validate inquiries
        if (empty($this->inquiries)) {
            throw new InvalidArgumentException('At least one inquiry is required');
        }

        foreach ($this->inquiries as $inquiry) {
            if (!isset($inquiry['service_type_code'])) {
                throw new InvalidArgumentException('Service type code is required for each inquiry');
            }

            if (strlen($inquiry['service_type_code']) !== 2) {
                throw new InvalidArgumentException('Service type code must be 2 characters');
            }
        }
    }
} 