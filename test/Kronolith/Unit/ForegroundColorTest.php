<?php

/**
 * Copyright 2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author     Torben Dannhauer <torben@dannhauer.de>
 * @category   Horde
 * @package    Kronolith
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/gpl GPL
 */

/**
 * Tests calendar foreground color selection (WCAG contrast vs legacy brightness).
 *
 * @covers Kronolith::foregroundColor
 */
class Kronolith_Unit_ForegroundColorTest extends PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider foregroundColorProvider
     */
    public function testForegroundColor(string $background, string $expected): void
    {
        if (!method_exists('Horde_Image', 'contrastColor')) {
            $this->markTestSkipped('Requires Horde_Image::contrastColor() from horde/Image#8');
        }

        $this->assertSame($expected, Kronolith::foregroundColor($background));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public function foregroundColorProvider(): array
    {
        return [
            'bright magenta prefers black' => ['#fb00ec', '#000'],
            'shorthand magenta prefers black' => ['#f0e', '#000'],
            'blue prefers white' => ['#0000ff', '#fff'],
            'dark green prefers white' => ['#008000', '#fff'],
            'white background prefers black' => ['#ffffff', '#000'],
            'black background prefers white' => ['#000000', '#fff'],
            'mid gray prefers black' => ['#808080', '#000'],
        ];
    }

    public function testForegroundColorUsesWcagNotBrightnessForMagenta(): void
    {
        if (!method_exists('Horde_Image', 'contrastColor')) {
            $this->markTestSkipped('Requires Horde_Image::contrastColor() from horde/Image#8');
        }

        $background = '#fb00ec';
        $legacy = Horde_Image::brightness($background) < 128 ? '#fff' : '#000';

        $this->assertSame('#fff', $legacy);
        $this->assertSame('#000', Kronolith::foregroundColor($background));
    }

    public function testCalendarForegroundMatchesForegroundColor(): void
    {
        if (!method_exists('Horde_Image', 'contrastColor')) {
            $this->markTestSkipped('Requires Horde_Image::contrastColor() from horde/Image#8');
        }

        $calendar = new Kronolith_Stub_ForegroundCalendar(['background' => '#fb00ec']);

        $this->assertSame(Kronolith::foregroundColor('#fb00ec'), $calendar->foreground());
    }

    public function testEventIconColorCandidatesWithoutHash(): void
    {
        if (!method_exists('Horde_Image', 'contrastColor')) {
            $this->markTestSkipped('Requires Horde_Image::contrastColor() from horde/Image#8');
        }

        $background = '#fb00ec';
        $iconColor = Horde_Image::contrastColor($background, 'fff', '000');

        $this->assertSame('000', $iconColor);
        $this->assertSame(
            ltrim(Kronolith::foregroundColor($background), '#'),
            $iconColor
        );
    }

    public function testLegacyBrightnessFallback(): void
    {
        $background = '#0000ff';
        $expected = Horde_Image::brightness($background) < 128 ? '#fff' : '#000';

        if (method_exists('Horde_Image', 'contrastColor')) {
            $this->assertSame($expected, Horde_Image::contrastColor($background, '#fff', '#000'));
        }

        $this->assertSame('#fff', $expected);
    }
}

/**
 * Minimal calendar stub for foreground() tests.
 */
class Kronolith_Stub_ForegroundCalendar extends Kronolith_Calendar
{
    public function name()
    {
        return 'test';
    }

    public function display()
    {
        return 'Test';
    }
}
