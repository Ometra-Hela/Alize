<?php

namespace Ometra\HelaAlize\Tests\Unit\Support;

use Carbon\CarbonImmutable;
use Ometra\HelaAlize\Support\PortIdGenerator;
use Ometra\HelaAlize\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class PortIdGeneratorTest extends TestCase
{
    private PortIdGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new PortIdGenerator();
    }

    #[Test]
    public function it_generates_valid_port_id_with_correct_length()
    {
        $portId = $this->generator->generate('XXX');

        $this->assertEquals(21, strlen($portId));
    }

    #[Test]
    public function it_generates_port_id_with_correct_format()
    {
        $portId = $this->generator->generate('ABC');

        // Should be: IDA (3) + Timestamp (14) + Sequence (4)
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{3}\d{14}\d{4}$/', $portId);
    }

    #[Test]
    public function it_starts_with_provided_ida()
    {
        $portId = $this->generator->generate('XYZ');

        $this->assertStringStartsWith('XYZ', $portId);
    }

    #[Test]
    public function it_validates_correct_port_id()
    {
        $validPortId = 'XXX202512101234560001';

        $this->assertTrue($this->generator->validate($validPortId));
    }

    #[Test]
    public function it_rejects_invalid_length()
    {
        $this->assertFalse($this->generator->validate('XXX2025'));
        $this->assertFalse($this->generator->validate('XXX20251210123456000100'));
    }

    #[Test]
    public function it_rejects_invalid_format()
    {
        $this->assertFalse($this->generator->validate('ABCD2025121012345601'));
        $this->assertFalse($this->generator->validate('XXX2025AB101234AB0001'));
    }

    #[Test]
    public function it_extracts_ida_correctly()
    {
        $portId = 'ABC202512101234560001';

        $this->assertEquals('ABC', $this->generator->extractIda($portId));
    }

    #[Test]
    public function it_extracts_timestamp_correctly()
    {
        $portId = 'XXX202512101234560001';

        $timestamp = $this->generator->extractTimestamp($portId);

        $this->assertInstanceOf(CarbonImmutable::class, $timestamp);
        $this->assertEquals('2025-12-10 12:34:56', $timestamp->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function it_extracts_sequence_correctly()
    {
        $portId = 'XXX202512101234560123';

        $this->assertEquals(123, $this->generator->extractSequence($portId));
    }

    #[Test]
    public function it_generates_with_custom_datetime()
    {
        $datetime = CarbonImmutable::parse('2025-12-31 23:59:59');
        $portId = $this->generator->generate('XXX', $datetime, 9999);

        $this->assertStringContainsString('20251231235959', $portId);
        $this->assertStringEndsWith('9999', $portId);
    }
}
