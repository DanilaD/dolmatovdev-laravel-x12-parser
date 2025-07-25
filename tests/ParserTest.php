<?php

namespace DolmatovDev\X12Parser\Tests;

use DolmatovDev\X12Parser\Parser;
use DolmatovDev\X12Parser\DTO\ParseResult;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    private Parser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new Parser();
    }

    public function test_parse_content_with_valid_270_transaction()
    {
        $content = "ISA*00*          *00*          *ZZ*SENDER         *ZZ*RECEIVER       *230101*1200*U*00401*000000001*0*P*>~GS*HS*SENDER*RECEIVER*20230101*1200*1*X*005010X279A1~ST*270*0001*005010X279A1~BHT*0022*13*10001234*20230101*1200~HL*1**20*1~NM1*PR*2*PAYER NAME*****PI*123456789~HL*2*1*21*1~NM1*1P*2*PROVIDER NAME*****SV*987654321~HL*3*2*22*0~TRN*1*123456789*987654321~NM1*IL*1*DOE*JOHN****MI*12345678901~DMG*D8*19800101~DTP*291*D8*20230101~EQ*30~SE*13*0001~GE*1*1~IEA*1*000000001~";

        $result = $this->parser->parseContent($content, '270');

        $this->assertInstanceOf(ParseResult::class, $result);
        $this->assertTrue($result->isSuccessful());
        $this->assertEmpty($result->errors);
        $this->assertNotEmpty($result->segments);
        $this->assertEquals('270', $result->transactionType);
    }

    public function test_parse_content_without_transaction_type()
    {
        $content = "ISA*00*          *00*          *ZZ*SENDER         *ZZ*RECEIVER       *230101*1200*U*00401*000000001*0*P*>~GS*HS*SENDER*RECEIVER*20230101*1200*1*X*005010X279A1~ST*270*0001*005010X279A1~";

        $result = $this->parser->parseContent($content);

        $this->assertInstanceOf(ParseResult::class, $result);
        $this->assertTrue($result->isSuccessful());
        $this->assertEquals('270', $result->transactionType);
    }

    public function test_parse_content_with_custom_delimiters()
    {
        $content = "ISA^00^ ^00^ ^ZZ^SENDER ^ZZ^RECEIVER ^230101^1200^U^00401^000000001^0^P^>~GS^HS^SENDER^RECEIVER^20230101^1200^1^X^005010X279A1~ST^270^0001^005010X279A1~";

        $this->parser->setDelimiters([
            'segment' => '~',
            'element' => '^',
            'sub_element' => '>'
        ]);

        $result = $this->parser->parseContent($content, '270');

        $this->assertInstanceOf(ParseResult::class, $result);
        

        
        $this->assertTrue($result->isSuccessful());
        $this->assertEquals('270', $result->transactionType);
    }

    public function test_get_default_delimiters()
    {
        $delimiters = $this->parser->getDefaultDelimiters();

        $this->assertEquals([
            'segment' => '~',
            'element' => '*',
            'sub_element' => '>'
        ], $delimiters);
    }

    public function test_set_delimiters_for_transaction()
    {
        $this->parser->setDelimitersForTransaction('270');

        $delimiters = $this->parser->getDelimiters();
        
        // Should use default delimiters if no config is available
        $this->assertEquals([
            'segment' => '~',
            'element' => '*',
            'sub_element' => '>'
        ], $delimiters);
    }

    public function test_get_supported_transaction_types()
    {
        $types = $this->parser->getSupportedTransactionTypes();

        $this->assertContains('270', $types);
        $this->assertContains('271', $types);
        $this->assertContains('837', $types);
        $this->assertContains('835', $types);
    }

    public function test_parse_file_not_found()
    {
        $result = $this->parser->parseFile('nonexistent_file.txt');

        $this->assertInstanceOf(ParseResult::class, $result);
        $this->assertFalse($result->isSuccessful());
        $this->assertNotEmpty($result->errors);
    }
} 