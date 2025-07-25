<?php

namespace DolmatovDev\X12Parser\Tests;

use PHPUnit\Framework\TestCase;
use DolmatovDev\X12Parser\Services\InputSanitizerService;

class InputSanitizerServiceTest extends TestCase
{
    private InputSanitizerService $sanitizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sanitizer = new InputSanitizerService();
    }

    public function test_sanitize_content_removes_control_characters(): void
    {
        $content = "ISA*00*          *00*          *ZZ*SENDER         *ZZ*RECEIVER       *230101*1200*U*00401*000000001*0*P*>~";
        $content .= "\0\x01\x02\x03"; // Add null bytes and control characters
        
        $result = $this->sanitizer->sanitizeContent($content);
        
        $this->assertStringNotContainsString("\0", $result);
        $this->assertStringNotContainsString("\x01", $result);
        $this->assertStringNotContainsString("\x02", $result);
        $this->assertStringNotContainsString("\x03", $result);
    }

    public function test_sanitize_content_normalizes_line_endings(): void
    {
        $content = "ISA*00*          *00*          *ZZ*SENDER         *ZZ*RECEIVER       *230101*1200*U*00401*000000001*0*P*>\r\n";
        $content .= "GS*HS*SENDER*RECEIVER*20230101*1200*1*X*005010X279A1\r";
        $content .= "ST*270*0001*005010X279A1\n";
        
        $result = $this->sanitizer->sanitizeContent($content);
        
        $this->assertStringNotContainsString("\r\n", $result);
        $this->assertStringNotContainsString("\r", $result);
        // Note: normalizeWhitespace removes newlines too, so we check the content is normalized
        $this->assertStringNotContainsString("  ", $result); // No multiple spaces
    }

    public function test_sanitize_content_normalizes_whitespace(): void
    {
        $content = "ISA*00*          *00*          *ZZ*SENDER         *ZZ*RECEIVER       *230101*1200*U*00401*000000001*0*P*>~";
        $content .= "  GS*HS*SENDER*RECEIVER*20230101*1200*1*X*005010X279A1  ~";
        $content .= "ST*270*0001*005010X279A1~";
        
        $result = $this->sanitizer->sanitizeContent($content);
        
        // Should not have multiple consecutive spaces
        $this->assertStringNotContainsString("  ", $result);
    }

    public function test_sanitize_segment_validates_length(): void
    {
        $longSegment = str_repeat("A", 106); // Exceeds MAX_SEGMENT_LENGTH (105)
        $delimiters = ['segment' => '~', 'element' => '*', 'sub_element' => '>'];
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Segment length exceeds maximum allowed length of 105 characters");
        
        $this->sanitizer->sanitizeSegment($longSegment, $delimiters);
    }

    public function test_sanitize_segment_validates_format(): void
    {
        $invalidSegment = "1*INVALID*SEGMENT"; // Doesn't start with 2-3 alphanumeric characters
        $delimiters = ['segment' => '~', 'element' => '*', 'sub_element' => '>'];
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Segment must start with 2-3 alphanumeric identifier");
        
        $this->sanitizer->sanitizeSegment($invalidSegment, $delimiters);
    }

    public function test_sanitize_segment_with_valid_data(): void
    {
        $segment = "ISA*00*          *00*          *ZZ*SENDER         *ZZ*RECEIVER       *230101*1200*U*00401*000000001*0*P*>";
        $delimiters = ['segment' => '~', 'element' => '*', 'sub_element' => '>'];
        
        $result = $this->sanitizer->sanitizeSegment($segment, $delimiters);
        
        // The sanitizer normalizes whitespace, so we expect the normalized version
        $expected = "ISA*00* *00* *ZZ*SENDER *ZZ*RECEIVER *230101*1200*U*00401*000000001*0*P*>";
        $this->assertEquals($expected, $result);
    }

    public function test_sanitize_element_validates_length(): void
    {
        $longElement = str_repeat("A", 81); // Exceeds MAX_ELEMENT_LENGTH (80)
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Element length exceeds maximum allowed length of 80 characters");
        
        $this->sanitizer->sanitizeElement($longElement);
    }

    public function test_sanitize_element_with_valid_data(): void
    {
        $element = "SENDER";
        $result = $this->sanitizer->sanitizeElement($element);
        
        $this->assertEquals($element, $result);
    }

    public function test_sanitize_element_removes_control_characters(): void
    {
        $element = "SENDER\x01\x02\x03";
        $result = $this->sanitizer->sanitizeElement($element);
        
        $this->assertEquals("SENDER", $result);
    }

    public function test_sanitize_json_data(): void
    {
        $data = [
            'subscriberId' => "12345\x01\x02", // Actual control characters
            'subscriberFirstName' => 'John',
            'subscriberLastName' => 'Doe',
            'inquiries' => [
                ['service_type_code' => "30\x03"] // Actual control character
            ]
        ];
        
        $result = $this->sanitizer->sanitizeJsonData($data);
        
        // Control characters should be removed
        $this->assertEquals('12345', $result['subscriberId']);
        $this->assertEquals('John', $result['subscriberFirstName']);
        $this->assertEquals('Doe', $result['subscriberLastName']);
        $this->assertEquals('30', $result['inquiries'][0]['service_type_code']);
    }

    public function test_is_content_safe_with_safe_content(): void
    {
        $content = "ISA*00*          *00*          *ZZ*SENDER         *ZZ*RECEIVER       *230101*1200*U*00401*000000001*0*P*>~";
        
        $result = $this->sanitizer->isContentSafe($content);
        
        $this->assertTrue($result);
    }

    public function test_is_content_safe_with_null_bytes(): void
    {
        $content = "ISA*00*          *00*          *ZZ*SENDER\0*ZZ*RECEIVER       *230101*1200*U*00401*000000001*0*P*>~";
        
        $result = $this->sanitizer->isContentSafe($content);
        
        $this->assertFalse($result);
    }

    public function test_is_content_safe_with_control_characters(): void
    {
        $content = "ISA*00*          *00*          *ZZ*SENDER\x01*ZZ*RECEIVER       *230101*1200*U*00401*000000001*0*P*>~";
        
        $result = $this->sanitizer->isContentSafe($content);
        
        $this->assertFalse($result);
    }

    public function test_get_validation_errors_with_empty_content(): void
    {
        $result = $this->sanitizer->getValidationErrors('');
        
        $this->assertContains("Content cannot be empty", $result);
    }

    public function test_get_validation_errors_with_dangerous_content(): void
    {
        $content = "ISA*00*          *00*          *ZZ*SENDER\0*ZZ*RECEIVER       *230101*1200*U*00401*000000001*0*P*>~";
        
        $result = $this->sanitizer->getValidationErrors($content);
        
        $this->assertContains("Content contains dangerous characters", $result);
    }

    public function test_get_validation_errors_with_large_content(): void
    {
        $content = str_repeat("A", 10001); // Exceeds 10KB limit
        
        $result = $this->sanitizer->getValidationErrors($content);
        
        $this->assertContains("Content is too large (max 10KB)", $result);
    }

    public function test_get_validation_errors_with_valid_content(): void
    {
        $content = "ISA*00*          *00*          *ZZ*SENDER         *ZZ*RECEIVER       *230101*1200*U*00401*000000001*0*P*>~";
        
        $result = $this->sanitizer->getValidationErrors($content);
        
        $this->assertEmpty($result);
    }

    public function test_sanitize_segment_with_custom_delimiters(): void
    {
        $segment = "ISA^00^          ^00^          ^ZZ^SENDER         ^ZZ^RECEIVER       ^230101^1200^U^00401^000000001^0^P^>";
        $delimiters = ['segment' => '~', 'element' => '^', 'sub_element' => '>'];
        
        $result = $this->sanitizer->sanitizeSegment($segment, $delimiters);
        
        // The sanitizer normalizes whitespace, so we expect the normalized version
        $expected = "ISA^00^ ^00^ ^ZZ^SENDER ^ZZ^RECEIVER ^230101^1200^U^00401^000000001^0^P^>";
        $this->assertEquals($expected, $result);
    }

    public function test_sanitize_content_preserves_valid_x12_characters(): void
    {
        $content = "ISA*00*          *00*          *ZZ*SENDER-NAME*ZZ*RECEIVER.NAME*230101*1200*U*00401*000000001*0*P*>~";
        $content .= "GS*HS*SENDER*RECEIVER*20230101*1200*1*X*005010X279A1~";
        $content .= "ST*270*0001*005010X279A1~";
        
        $result = $this->sanitizer->sanitizeContent($content);
        
        // Should preserve valid X12 characters like hyphens and dots
        $this->assertStringContainsString("SENDER-NAME", $result);
        $this->assertStringContainsString("RECEIVER.NAME", $result);
    }
} 