<?php

/**
 * Tests that kronolith methods using Horde_Nls::getLangInfo() produce correct
 * locale-aware output (format strings, day names, month names).
 * @coversNothing
 */
class Kronolith_Unit_NlsLangInfoTest extends PHPUnit\Framework\TestCase
{
    /**
     * Test Kronolith::parseDate() which uses getLangInfo(D_FMT) to determine
     * the date format for parsing.
     */
    public function testParseDateUsesLocaleDateFormat()
    {
        $format = Horde_Nls::getLangInfo(D_FMT);
        $dateStr = strftime($format, mktime(0, 0, 0, 3, 20, 2025));

        $date = Kronolith::parseDate($dateStr, false);

        $this->assertInstanceOf(Horde_Date::class, $date);
        $this->assertSame(2025, (int) $date->year);
        $this->assertSame(3, (int) $date->month);
        $this->assertSame(20, (int) $date->mday);
    }

    /**
     * Test that getLangInfo(D_FMT) returns a non-empty format string.
     * This is what Ajax::_addBaseVars() and View_Sidebar use.
     */
    public function testGetLangInfoReturnsDfmtString()
    {
        $format = Horde_Nls::getLangInfo(D_FMT);

        $this->assertIsString($format);
        $this->assertNotEmpty($format);
        $this->assertStringContainsString('%', $format);
    }

    /**
     * Test that getLangInfo(MON_*) returns month names.
     * Used by Ajax::_addBaseVars() to build JS month arrays.
     */
    public function testGetLangInfoReturnsMonthNames()
    {
        $months = [];
        for ($i = 1; $i <= 12; ++$i) {
            $months[] = Horde_Nls::getLangInfo(constant('MON_' . $i));
        }

        $this->assertCount(12, $months);
        foreach ($months as $month) {
            $this->assertIsString($month);
            $this->assertNotEmpty($month);
        }
    }

    /**
     * Test that getLangInfo(DAY_*) returns weekday names.
     * Used by View_Sidebar and Ajax::_addBaseVars().
     */
    public function testGetLangInfoReturnsWeekdayNames()
    {
        $days = [];
        for ($i = 1; $i <= 7; ++$i) {
            $days[] = Horde_Nls::getLangInfo(constant('DAY_' . $i));
        }

        $this->assertCount(7, $days);
        foreach ($days as $day) {
            $this->assertIsString($day);
            $this->assertNotEmpty($day);
        }
    }
}
