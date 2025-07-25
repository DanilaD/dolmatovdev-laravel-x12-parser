<?php

namespace DolmatovDev\X12Parser\Validators;

use DolmatovDev\X12Parser\DTO\ValidationResult;
use DolmatovDev\X12Parser\DTO\Eligibility270DTO;

/**
 * Validator for ANSI X12 270 (Eligibility/Benefit Inquiry) transaction sets.
 * 
 * This validator ensures compliance with the X12 270 specification,
 * checking required segments, element formats, and business rules.
 * 
 * @see https://x12.org/codes/270-eligibility-benefit-inquiry
 */
class Validator270 implements AnsiValidatorInterface
{
    /**
     * Required segments for 270 transaction.
     */
    private const REQUIRED_SEGMENTS = [
        'ISA', // Interchange Control Header
        'GS',  // Functional Group Header
        'ST',  // Transaction Set Header
        'BHT', // Beginning of Hierarchical Transaction
        'HL',  // Hierarchical Level
        'NM1', // Subscriber Name
        'SE',  // Transaction Set Trailer
        'GE',  // Functional Group Trailer
        'IEA', // Interchange Control Trailer
    ];

    /**
     * Optional segments for 270 transaction.
     */
    private const OPTIONAL_SEGMENTS = [
        'TRN', // Subscriber Trace Number
        'DMG', // Subscriber Demographics
        'DTP', // Date/Time Reference
        'EQ',  // Subscriber Eligibility or Benefit Inquiry
        'QTY', // Quantity
    ];

    /**
     * Current delimiters for parsing segments.
     */
    private array $delimiters;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->delimiters = [
            'segment' => '~',
            'element' => '*',
            'sub_element' => '>'
        ];
    }

    /**
     * Set delimiters for parsing.
     * 
     * @param array $delimiters Delimiters array
     */
    public function setDelimiters(array $delimiters): void
    {
        $this->delimiters = $delimiters;
    }

    /**
     * Validate the structure and content of 270 segments.
     * 
     * @param array $segments Array of ANSI segments
     * @return ValidationResult Contains validation status, errors, and parsed data
     */
    public function validate(array $segments): ValidationResult
    {
        $errors = [];
        $warnings = [];
        $parsedData = [];

        // Check for required segments
        $missingSegments = $this->checkRequiredSegments($segments);
        if (!empty($missingSegments)) {
            $errors[] = 'Missing required segments: ' . implode(', ', $missingSegments);
        }

        // Validate segment order
        $orderErrors = $this->validateSegmentOrder($segments);
        $errors = array_merge($errors, $orderErrors);

        // Validate individual segments
        $segmentErrors = $this->validateIndividualSegments($segments);
        $errors = array_merge($errors, $segmentErrors);

        // Parse data if validation passes
        if (empty($errors)) {
            try {
                $parsedData = $this->parseSegments($segments);
            } catch (\Exception $e) {
                $errors[] = 'Error parsing segments: ' . $e->getMessage();
            }
        }

        // Check for warnings
        $warnings = $this->checkWarnings($segments);

        if (empty($errors)) {
            return ValidationResult::success($parsedData, '270');
        }

        return ValidationResult::failure($errors, $warnings, '270');
    }

    /**
     * Get the transaction type this validator handles.
     * 
     * @return string Transaction type
     */
    public function getTransactionType(): string
    {
        return '270';
    }

    /**
     * Get the required segments for this transaction type.
     * 
     * @return array Array of required segment IDs
     */
    public function getRequiredSegments(): array
    {
        return self::REQUIRED_SEGMENTS;
    }

    /**
     * Get the optional segments for this transaction type.
     * 
     * @return array Array of optional segment IDs
     */
    public function getOptionalSegments(): array
    {
        return self::OPTIONAL_SEGMENTS;
    }

    /**
     * Check for missing required segments.
     * 
     * @param array $segments Array of segments
     * @return array Array of missing segment IDs
     */
    private function checkRequiredSegments(array $segments): array
    {
        $foundSegments = [];
        
        foreach ($segments as $segment) {
            $segmentId = explode('*', $segment)[0];
            $foundSegments[] = $segmentId;
        }

        return array_diff(self::REQUIRED_SEGMENTS, $foundSegments);
    }

    /**
     * Validate the order of segments.
     * 
     * @param array $segments Array of segments
     * @return array Array of order errors
     */
    private function validateSegmentOrder(array $segments): array
    {
        $errors = [];
        $segmentIds = [];

        foreach ($segments as $segment) {
            $segmentIds[] = explode('*', $segment)[0];
        }

        // Check ISA is first
        if (!empty($segmentIds) && $segmentIds[0] !== 'ISA') {
            $errors[] = 'ISA segment must be the first segment';
        }

        // Check IEA is last
        if (!empty($segmentIds) && end($segmentIds) !== 'IEA') {
            $errors[] = 'IEA segment must be the last segment';
        }

        // Check ST comes after GS
        $gsIndex = array_search('GS', $segmentIds);
        $stIndex = array_search('ST', $segmentIds);
        
        if ($gsIndex !== false && $stIndex !== false && $stIndex < $gsIndex) {
            $errors[] = 'ST segment must come after GS segment';
        }

        return $errors;
    }

    /**
     * Validate individual segments.
     * 
     * @param array $segments Array of segments
     * @return array Array of validation errors
     */
    private function validateIndividualSegments(array $segments): array
    {
        $errors = [];

        foreach ($segments as $segment) {
            $segmentId = explode($this->delimiters['element'], $segment)[0];
            
            switch ($segmentId) {
                case 'ISA':
                    $errors = array_merge($errors, $this->validateISA($segment));
                    break;
                case 'ST':
                    $errors = array_merge($errors, $this->validateST($segment));
                    break;
                case 'NM1':
                    $errors = array_merge($errors, $this->validateNM1($segment));
                    break;
            }
        }

        return $errors;
    }

    /**
     * Validate ISA segment.
     * 
     * @param string $segment ISA segment
     * @return array Array of validation errors
     */
    private function validateISA(string $segment): array
    {
        $errors = [];
        $elements = explode($this->delimiters['element'], $segment);

        // ISA should have 16 elements
        if (count($elements) !== 16) {
            $errors[] = 'ISA segment must have exactly 16 elements';
        }

        // Check authorization information qualifier (element 1)
        if (!in_array($elements[1] ?? '', ['00', '03'])) {
            $errors[] = 'ISA01: Invalid authorization information qualifier';
        }

        // Check security information qualifier (element 3)
        if (!in_array($elements[3] ?? '', ['00', '01'])) {
            $errors[] = 'ISA03: Invalid security information qualifier';
        }

        return $errors;
    }

    /**
     * Validate ST segment.
     * 
     * @param string $segment ST segment
     * @return array Array of validation errors
     */
    private function validateST(string $segment): array
    {
        $errors = [];
        $elements = explode($this->delimiters['element'], $segment);

        // ST should have at least 2 elements
        if (count($elements) < 2) {
            $errors[] = 'ST segment must have at least 2 elements';
        }

        // Check transaction set identifier code (element 1)
        if (($elements[1] ?? '') !== '270') {
            $errors[] = 'ST01: Transaction set identifier code must be 270';
        }

        return $errors;
    }

    /**
     * Validate NM1 segment.
     * 
     * @param string $segment NM1 segment
     * @return array Array of validation errors
     */
    private function validateNM1(string $segment): array
    {
        $errors = [];
        $elements = explode($this->delimiters['element'], $segment);

        // NM1 should have at least 3 elements
        if (count($elements) < 3) {
            $errors[] = 'NM1 segment must have at least 3 elements';
        }

        // Check entity identifier code (element 1)
        $validEntityCodes = ['IL', '30', '31', '36', '6Y', '9K', 'D3', 'E1', 'EJ', 'EXS', 'GB', 'GD', 'J6', 'LR', 'P3', 'P4', 'P5', 'P6', 'P7', 'P8', 'P9', 'PA', 'PB', 'PC', 'PD', 'PE', 'PF', 'PG', 'PH', 'PI', 'PJ', 'PK', 'PL', 'PM', 'PN', 'PO', 'PP', 'PQ', 'PR', 'PS', 'PT', 'PU', 'PV', 'PW', 'PX', 'PY', 'PZ'];
        
        if (!in_array($elements[1] ?? '', $validEntityCodes)) {
            $errors[] = 'NM1*01: Invalid entity identifier code';
        }

        return $errors;
    }

    /**
     * Parse segments into structured data.
     * 
     * @param array $segments Array of segments
     * @return array Parsed data
     */
    private function parseSegments(array $segments): array
    {
        $data = [
            'interchange' => [],
            'functional_group' => [],
            'transaction' => [],
            'subscriber' => [],
        ];

        foreach ($segments as $segment) {
            $segmentId = explode($this->delimiters['element'], $segment)[0];
            
            switch ($segmentId) {
                case 'ISA':
                    $data['interchange'] = $this->parseISA($segment);
                    break;
                case 'GS':
                    $data['functional_group'] = $this->parseGS($segment);
                    break;
                case 'ST':
                    $data['transaction'] = $this->parseST($segment);
                    break;
                case 'NM1':
                    if (str_contains($segment, $this->delimiters['element'] . 'IL' . $this->delimiters['element'])) {
                        $data['subscriber'] = $this->parseNM1($segment);
                    }
                    break;
            }
        }

        return $data;
    }

    /**
     * Parse ISA segment.
     * 
     * @param string $segment ISA segment
     * @return array Parsed ISA data
     */
    private function parseISA(string $segment): array
    {
        $elements = explode($this->delimiters['element'], $segment);
        
        return [
            'authorization_qualifier' => $elements[1] ?? '',
            'authorization_info' => $elements[2] ?? '',
            'security_qualifier' => $elements[3] ?? '',
            'security_info' => $elements[4] ?? '',
            'sender_id_qualifier' => $elements[5] ?? '',
            'sender_id' => $elements[6] ?? '',
            'receiver_id_qualifier' => $elements[7] ?? '',
            'receiver_id' => $elements[8] ?? '',
            'date' => $elements[9] ?? '',
            'time' => $elements[10] ?? '',
            'control_standards' => $elements[11] ?? '',
            'version_number' => $elements[12] ?? '',
            'control_number' => $elements[13] ?? '',
            'acknowledgment_requested' => $elements[14] ?? '',
            'usage_indicator' => $elements[15] ?? '',
        ];
    }

    /**
     * Parse GS segment.
     * 
     * @param string $segment GS segment
     * @return array Parsed GS data
     */
    private function parseGS(string $segment): array
    {
        $elements = explode($this->delimiters['element'], $segment);
        
        return [
            'functional_identifier_code' => $elements[1] ?? '',
            'application_sender_code' => $elements[2] ?? '',
            'application_receiver_code' => $elements[3] ?? '',
            'date' => $elements[4] ?? '',
            'time' => $elements[5] ?? '',
            'group_control_number' => $elements[6] ?? '',
            'responsible_agency_code' => $elements[7] ?? '',
            'version_identifier_code' => $elements[8] ?? '',
        ];
    }

    /**
     * Parse ST segment.
     * 
     * @param string $segment ST segment
     * @return array Parsed ST data
     */
    private function parseST(string $segment): array
    {
        $elements = explode($this->delimiters['element'], $segment);
        
        return [
            'transaction_set_identifier_code' => $elements[1] ?? '',
            'transaction_set_control_number' => $elements[2] ?? '',
        ];
    }

    /**
     * Parse NM1 segment.
     * 
     * @param string $segment NM1 segment
     * @return array Parsed NM1 data
     */
    private function parseNM1(string $segment): array
    {
        $elements = explode($this->delimiters['element'], $segment);
        
        return [
            'entity_identifier_code' => $elements[1] ?? '',
            'entity_type_qualifier' => $elements[2] ?? '',
            'last_name' => $elements[3] ?? '',
            'first_name' => $elements[4] ?? '',
            'middle_name' => $elements[5] ?? '',
            'prefix' => $elements[6] ?? '',
            'suffix' => $elements[7] ?? '',
            'identification_code_qualifier' => $elements[8] ?? '',
            'identification_code' => $elements[9] ?? '',
        ];
    }

    /**
     * Check for warnings.
     * 
     * @param array $segments Array of segments
     * @return array Array of warnings
     */
    private function checkWarnings(array $segments): array
    {
        $warnings = [];

        // Check for deprecated segments
        foreach ($segments as $segment) {
            $segmentId = explode($this->delimiters['element'], $segment)[0];
            
            if (in_array($segmentId, ['N3', 'N4'])) {
                $warnings[] = "Segment {$segmentId} is deprecated in 270 transactions";
            }
        }

        return $warnings;
    }
} 