<?php

declare(strict_types=1);

namespace Tests\Unit\OnlyOffice;

use App\Domain\OnlyOffice\OnlyOfficeSupport;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class OnlyOfficeSupportTest extends TestCase
{
    public function test_office_extensions_are_editable(): void
    {
        $this->assertTrue(OnlyOfficeSupport::isEditable('docx'));
        $this->assertTrue(OnlyOfficeSupport::isEditable('XLSX'));
        $this->assertTrue(OnlyOfficeSupport::isEditable('txt'));
    }

    public function test_pdf_and_images_are_not_editable(): void
    {
        $this->assertFalse(OnlyOfficeSupport::isEditable('pdf'));
        $this->assertFalse(OnlyOfficeSupport::isEditable('png'));
    }

    public function test_document_type_matches_the_extension_family(): void
    {
        $this->assertSame('word', OnlyOfficeSupport::documentType('docx'));
        $this->assertSame('cell', OnlyOfficeSupport::documentType('xlsx'));
        $this->assertSame('slide', OnlyOfficeSupport::documentType('pptx'));
    }

    public function test_document_type_rejects_a_non_editable_extension(): void
    {
        $this->expectException(InvalidArgumentException::class);
        OnlyOfficeSupport::documentType('pdf');
    }
}
