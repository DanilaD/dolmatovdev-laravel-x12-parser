<?php

namespace DolmatovDev\X12Parser\Services;

use DanilaD\NpiLook\NpiLook;

/**
 * Service for integrating NPI (National Provider Identifier) lookup functionality
 * with X12 parser operations.
 * 
 * This service provides methods to validate and enrich provider information
 * using the NPPES NPI Registry API.
 */
class NpiLookupService
{
    private NpiLook $npiLook;

    public function __construct()
    {
        $this->npiLook = new NpiLook();
    }

    /**
     * Look up provider information by NPI number.
     *
     * @param string $npi The NPI number to look up
     * @return array|null Provider information or null if not found
     */
    public function lookupByNpi(string $npi): ?array
    {
        try {
            $result = $this->npiLook->lookup($npi);
            
            if ($result && !empty($result)) {
                return $this->formatProviderData($result[0]);
            }
            
            return null;
        } catch (\Exception $e) {
            // Log error and return null
            error_log("NPI Lookup error for NPI {$npi}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Search for providers by name.
     *
     * @param string $firstName Provider first name
     * @param string $lastName Provider last name
     * @param string|null $state Provider state (optional)
     * @return array Array of matching providers
     */
    public function searchByName(string $firstName, string $lastName, ?string $state = null): array
    {
        try {
            $params = [
                'first_name' => $firstName,
                'last_name' => $lastName,
            ];
            
            if ($state) {
                $params['state'] = $state;
            }
            
            $results = $this->npiLook->search($params);
            
            return array_map([$this, 'formatProviderData'], $results);
        } catch (\Exception $e) {
            error_log("NPI Search error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Validate if an NPI number is valid and active.
     *
     * @param string $npi The NPI number to validate
     * @return bool True if valid and active, false otherwise
     */
    public function validateNpi(string $npi): bool
    {
        $provider = $this->lookupByNpi($npi);
        
        if (!$provider) {
            return false;
        }
        
        // Check if the provider is active
        return $provider['status'] === 'A';
    }

    /**
     * Get provider taxonomy information.
     *
     * @param string $npi The NPI number
     * @return array Array of taxonomy information
     */
    public function getProviderTaxonomies(string $npi): array
    {
        $provider = $this->lookupByNpi($npi);
        
        if (!$provider) {
            return [];
        }
        
        return $provider['taxonomies'] ?? [];
    }

    /**
     * Enrich X12 provider data with NPI information.
     *
     * @param array $x12ProviderData Provider data from X12 segments
     * @return array Enriched provider data
     */
    public function enrichX12ProviderData(array $x12ProviderData): array
    {
        $enriched = $x12ProviderData;
        
        // If we have an NPI, look it up
        if (isset($x12ProviderData['npi'])) {
            $npiInfo = $this->lookupByNpi($x12ProviderData['npi']);
            
            if ($npiInfo) {
                $enriched['npi_validated'] = true;
                $enriched['provider_name'] = $npiInfo['name'];
                $enriched['provider_address'] = $npiInfo['address'];
                $enriched['provider_taxonomies'] = $npiInfo['taxonomies'];
                $enriched['provider_status'] = $npiInfo['status'];
            } else {
                $enriched['npi_validated'] = false;
                $enriched['npi_error'] = 'NPI not found in registry';
            }
        }
        
        return $enriched;
    }

    /**
     * Format provider data from NPI lookup response.
     *
     * @param array $rawData Raw data from NPI lookup
     * @return array Formatted provider data
     */
    private function formatProviderData(array $rawData): array
    {
        return [
            'npi' => $rawData['number'] ?? '',
            'name' => $this->formatProviderName($rawData),
            'address' => $this->formatProviderAddress($rawData),
            'taxonomies' => $this->formatTaxonomies($rawData),
            'status' => $rawData['enumeration_type'] ?? '',
            'type' => $rawData['basic']['organization_name'] ? 'organization' : 'individual',
            'created_date' => $rawData['created_epoch'] ?? '',
            'last_updated' => $rawData['last_updated_epoch'] ?? '',
        ];
    }

    /**
     * Format provider name from raw data.
     *
     * @param array $rawData Raw NPI data
     * @return string Formatted provider name
     */
    private function formatProviderName(array $rawData): string
    {
        if (isset($rawData['basic']['organization_name'])) {
            return $rawData['basic']['organization_name'];
        }
        
        $firstName = $rawData['basic']['first_name'] ?? '';
        $lastName = $rawData['basic']['last_name'] ?? '';
        $middleName = $rawData['basic']['middle_name'] ?? '';
        
        $name = trim($firstName . ' ' . $middleName . ' ' . $lastName);
        
        return $name ?: 'Unknown';
    }

    /**
     * Format provider address from raw data.
     *
     * @param array $rawData Raw NPI data
     * @return array Formatted address
     */
    private function formatProviderAddress(array $rawData): array
    {
        $addresses = $rawData['addresses'] ?? [];
        
        if (empty($addresses)) {
            return [];
        }
        
        // Get the primary address
        $primaryAddress = null;
        foreach ($addresses as $address) {
            if (($address['address_purpose'] ?? '') === 'PRIMARY') {
                $primaryAddress = $address;
                break;
            }
        }
        
        // If no primary address, use the first one
        if (!$primaryAddress && !empty($addresses)) {
            $primaryAddress = $addresses[0];
        }
        
        if (!$primaryAddress) {
            return [];
        }
        
        return [
            'line1' => $primaryAddress['address_1'] ?? '',
            'line2' => $primaryAddress['address_2'] ?? '',
            'city' => $primaryAddress['city'] ?? '',
            'state' => $primaryAddress['state'] ?? '',
            'zip' => $primaryAddress['postal_code'] ?? '',
            'country' => $primaryAddress['country_name'] ?? '',
            'phone' => $primaryAddress['telephone_number'] ?? '',
        ];
    }

    /**
     * Format taxonomy information from raw data.
     *
     * @param array $rawData Raw NPI data
     * @return array Formatted taxonomy data
     */
    private function formatTaxonomies(array $rawData): array
    {
        $taxonomies = $rawData['taxonomies'] ?? [];
        
        return array_map(function ($taxonomy) {
            return [
                'code' => $taxonomy['code'] ?? '',
                'desc' => $taxonomy['desc'] ?? '',
                'primary' => ($taxonomy['primary'] ?? false),
                'state' => $taxonomy['state'] ?? '',
                'license' => $taxonomy['license'] ?? '',
            ];
        }, $taxonomies);
    }
} 