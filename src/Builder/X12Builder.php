<?php

namespace DolmatovDev\X12Parser\Builder;

use DolmatovDev\X12Parser\DTO\Eligibility270DTO;
use DolmatovDev\X12Parser\Exceptions\AnsiFileException;
use DolmatovDev\X12Parser\Services\InputSanitizerService;
use DolmatovDev\X12Parser\Traits\HasDelimiters;

/**
 * Builder for creating ANSI X12 files from structured data.
 * 
 * This class handles the conversion of DTOs and arrays into
 * properly formatted ANSI X12 files.
 */
class X12Builder
{
    use HasDelimiters;

    /**
     * Input sanitizer service for cleaning input data.
     */
    private InputSanitizerService $sanitizer;

    /**
     * Constructor to initialize with default delimiters.
     */
    public function __construct()
    {
        $this->initializeDelimiters();
        $this->sanitizer = new InputSanitizerService();
    }

    /**
     * Build ANSI content from Eligibility270DTO.
     * 
     * @param Eligibility270DTO $dto The DTO to build from
     * @param string|null $transactionType Optional transaction type for custom delimiters
     * @return string ANSI formatted content
     */
    public function buildFrom270DTO(Eligibility270DTO $dto, ?string $transactionType = null): string
    {
        // Set delimiters based on transaction type if provided
        if ($transactionType) {
            $this->setDelimitersForTransaction($transactionType);
        }

        // Validate DTO before building
        $dto->validate();

        $segments = [];

        // Build ISA segment
        $segments[] = $this->buildISASegment($dto);

        // Build GS segment
        $segments[] = $this->buildGSSegment($dto);

        // Build ST segment
        $segments[] = $this->buildSTSegment($dto);

        // Build BHT segment
        $segments[] = $this->buildBHTSegment($dto);

        // Build HL segment
        $segments[] = $this->buildHLSegment($dto);

        // Build NM1 segment for subscriber
        $segments[] = $this->buildNM1Segment($dto);

        // Build TRN segment if member ID exists
        if ($dto->subscriberMemberId) {
            $segments[] = $this->buildTRNSegment($dto);
        }

        // Build DMG segment if demographics exist
        if ($dto->hasDemographics()) {
            $segments[] = $this->buildDMGSegment($dto);
        }

        // Build EQ segments for inquiries
        foreach ($dto->inquiries as $inquiry) {
            $segments[] = $this->buildEQSegment($inquiry);
        }

        // Build SE segment
        $segments[] = $this->buildSESegment($dto);

        // Build GE segment
        $segments[] = $this->buildGESegment($dto);

        // Build IEA segment
        $segments[] = $this->buildIEASegment($dto);

        return implode($this->currentDelimiters['segment'], $segments) . $this->currentDelimiters['segment'];
    }

    /**
     * Build ANSI content from array data.
     * 
     * @param array $data Array data
     * @param string $transactionType Transaction type (e.g., '270')
     * @return string ANSI formatted content
     */
    public function buildFromArray(array $data, string $transactionType = '270'): string
    {
        return match($transactionType) {
            '270' => $this->buildFrom270DTO(Eligibility270DTO::fromArray($data)),
            default => throw new AnsiFileException("Unsupported transaction type: {$transactionType}")
        };
    }

    /**
     * Build ISA (Interchange Control Header) segment.
     * 
     * @param Eligibility270DTO $dto The DTO
     * @return string ISA segment
     */
    private function buildISASegment(Eligibility270DTO $dto): string
    {
        $interchange = $dto->interchangeData;
        
        return implode($this->currentDelimiters['element'], [
            'ISA',
            $interchange['authorization_qualifier'] ?? '00',
            str_pad($interchange['authorization_info'] ?? '', 10),
            $interchange['security_qualifier'] ?? '00',
            str_pad($interchange['security_info'] ?? '', 10),
            $interchange['sender_id_qualifier'] ?? 'ZZ',
            str_pad($interchange['sender_id'] ?? 'SENDER', 15),
            $interchange['receiver_id_qualifier'] ?? 'ZZ',
            str_pad($interchange['receiver_id'] ?? 'RECEIVER', 15),
            $interchange['date'] ?? date('ymd'),
            $interchange['time'] ?? date('Hi'),
            $interchange['control_standards'] ?? 'U',
            $interchange['version_number'] ?? '00401',
            str_pad($interchange['control_number'] ?? '000000001', 9, '0', STR_PAD_LEFT),
            $interchange['acknowledgment_requested'] ?? '0',
            $interchange['usage_indicator'] ?? 'P',
            $interchange['element_separator'] ?? '>',
        ]);
    }

    /**
     * Build GS (Functional Group Header) segment.
     * 
     * @param Eligibility270DTO $dto The DTO
     * @return string GS segment
     */
    private function buildGSSegment(Eligibility270DTO $dto): string
    {
        $functionalGroup = $dto->functionalGroupData;
        
        return implode($this->currentDelimiters['element'], [
            'GS',
            $functionalGroup['functional_identifier_code'] ?? 'HS',
            $functionalGroup['application_sender_code'] ?? 'SENDER',
            $functionalGroup['application_receiver_code'] ?? 'RECEIVER',
            $functionalGroup['date'] ?? date('Ymd'),
            $functionalGroup['time'] ?? date('Hi'),
            str_pad($functionalGroup['group_control_number'] ?? '1', 9, '0', STR_PAD_LEFT),
            $functionalGroup['responsible_agency_code'] ?? 'X',
            $functionalGroup['version_identifier_code'] ?? '005010X279A1',
        ]);
    }

    /**
     * Build ST (Transaction Set Header) segment.
     * 
     * @param Eligibility270DTO $dto The DTO
     * @return string ST segment
     */
    private function buildSTSegment(Eligibility270DTO $dto): string
    {
        $transaction = $dto->transactionData;
        
        return implode($this->currentDelimiters['element'], [
            'ST',
            $transaction['transaction_set_identifier_code'] ?? '270',
            str_pad($transaction['transaction_set_control_number'] ?? '0001', 9, '0', STR_PAD_LEFT),
        ]);
    }

    /**
     * Build BHT (Beginning of Hierarchical Transaction) segment.
     * 
     * @param Eligibility270DTO $dto The DTO
     * @return string BHT segment
     */
    private function buildBHTSegment(Eligibility270DTO $dto): string
    {
        return implode($this->currentDelimiters['element'], [
            'BHT',
            '0022', // Hierarchical Structure Code
            '13',   // Transaction Set Purpose Code
            str_pad($dto->subscriberId, 50), // Reference Identification
            date('Ymd'), // Date
            date('Hi'),  // Time
            'RT',   // Transaction Type Code
        ]);
    }

    /**
     * Build HL (Hierarchical Level) segment.
     * 
     * @param Eligibility270DTO $dto The DTO
     * @return string HL segment
     */
    private function buildHLSegment(Eligibility270DTO $dto): string
    {
        return implode($this->currentDelimiters['element'], [
            'HL',
            '1',    // Hierarchical ID Number
            '',     // Parent Hierarchical ID Number
            '20',   // Hierarchical Level Code
            '1',    // Hierarchical Child Code
        ]);
    }

    /**
     * Build NM1 (Subscriber Name) segment.
     * 
     * @param Eligibility270DTO $dto The DTO
     * @return string NM1 segment
     */
    private function buildNM1Segment(Eligibility270DTO $dto): string
    {
        return implode($this->currentDelimiters['element'], [
            'NM1',
            'IL',   // Entity Identifier Code (Subscriber)
            '1',    // Entity Type Qualifier (Person)
            $dto->subscriberLastName,
            $dto->subscriberFirstName,
            $dto->subscriberMiddleName,
            '',     // Name Prefix
            '',     // Name Suffix
            'MI',   // Identification Code Qualifier (Member Identification Number)
            $dto->subscriberId,
        ]);
    }

    /**
     * Build TRN (Subscriber Trace Number) segment.
     * 
     * @param Eligibility270DTO $dto The DTO
     * @return string TRN segment
     */
    private function buildTRNSegment(Eligibility270DTO $dto): string
    {
        return implode($this->currentDelimiters['element'], [
            'TRN',
            '1',    // Trace Type Code
            $dto->subscriberMemberId,
            '9' . $dto->subscriberId, // Originating Company Identifier
        ]);
    }

    /**
     * Build DMG (Subscriber Demographics) segment.
     * 
     * @param Eligibility270DTO $dto The DTO
     * @return string DMG segment
     */
    private function buildDMGSegment(Eligibility270DTO $dto): string
    {
        return implode($this->currentDelimiters['element'], [
            'DMG',
            'D8',   // Date Time Period Format Qualifier
            $dto->subscriberDateOfBirth,
            $dto->subscriberGender,
            '',     // Marital Status Code
            '',     // Race or Ethnicity Code
            '',     // Citizenship Status Code
            '',     // Country Code
            '',     // Basis of Verification Code
            '',     // Quantity
            '',     // Code List Qualifier Code
            '',     // Industry Code
        ]);
    }

    /**
     * Build EQ (Subscriber Eligibility or Benefit Inquiry) segment.
     * 
     * @param array $inquiry Inquiry data
     * @return string EQ segment
     */
    private function buildEQSegment(array $inquiry): string
    {
        return implode($this->currentDelimiters['element'], [
            'EQ',
            $inquiry['service_type_code'] ?? '30', // Service Type Code
            $inquiry['medical_procedure_code'] ?? '', // Medical Procedure Code
            $inquiry['diagnosis_code_pointer'] ?? '', // Diagnosis Code Pointer
            $inquiry['date'] ?? '', // Date
            $inquiry['quantity'] ?? '', // Quantity
            $inquiry['unit_of_measurement_code'] ?? '', // Unit of Measurement Code
        ]);
    }

    /**
     * Build SE (Transaction Set Trailer) segment.
     * 
     * @param Eligibility270DTO $dto The DTO
     * @return string SE segment
     */
    private function buildSESegment(Eligibility270DTO $dto): string
    {
        // Count segments between ST and SE (excluding ST and SE themselves)
        $segmentCount = 8; // BHT, HL, NM1, TRN (if exists), DMG (if exists), EQ (if exists)
        
        if ($dto->subscriberMemberId) {
            $segmentCount++;
        }
        
        if ($dto->hasDemographics()) {
            $segmentCount++;
        }
        
        $segmentCount += count($dto->inquiries);

        return implode($this->currentDelimiters['element'], [
            'SE',
            $segmentCount,
            str_pad($dto->transactionData['transaction_set_control_number'] ?? '0001', 9, '0', STR_PAD_LEFT),
        ]);
    }

    /**
     * Build GE (Functional Group Trailer) segment.
     * 
     * @param Eligibility270DTO $dto The DTO
     * @return string GE segment
     */
    private function buildGESegment(Eligibility270DTO $dto): string
    {
        $functionalGroup = $dto->functionalGroupData;
        
        return implode($this->currentDelimiters['element'], [
            'GE',
            '1', // Number of Transaction Sets Included
            str_pad($functionalGroup['group_control_number'] ?? '1', 9, '0', STR_PAD_LEFT),
        ]);
    }

    /**
     * Build IEA (Interchange Control Trailer) segment.
     * 
     * @param Eligibility270DTO $dto The DTO
     * @return string IEA segment
     */
    private function buildIEASegment(Eligibility270DTO $dto): string
    {
        $interchange = $dto->interchangeData;
        
        return implode($this->currentDelimiters['element'], [
            'IEA',
            '1', // Number of Included Functional Groups
            str_pad($interchange['control_number'] ?? '000000001', 9, '0', STR_PAD_LEFT),
        ]);
    }




} 