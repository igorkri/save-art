<?php

namespace Tests\Feature;

use App\Enums\DocumentType;
use App\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_document_can_be_created(): void
    {
        $document = Document::create([
            'name' => ['uk' => 'Контракт', 'en' => 'Contract'],
            'type' => DocumentType::Contract,
            'description' => ['uk' => 'Тестовий опис', 'en' => 'Test description'],
            'file_path' => 'documents/test.pdf',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('documents', [
            'type' => DocumentType::Contract->value,
        ]);

        $this->assertEquals(DocumentType::Contract, $document->type);
        $this->assertEquals('Контракт', $document->getTranslation('name', 'uk'));
        $this->assertEquals('Contract', $document->getTranslation('name', 'en'));
        $this->assertEquals('Тестовий опис', $document->getTranslation('description', 'uk'));
        $this->assertEquals('Test description', $document->getTranslation('description', 'en'));
    }

    public function test_document_file_metadata_is_updated_on_creation(): void
    {
        Storage::disk('public')->put('documents/test.pdf', 'fake pdf content');

        $document = Document::create([
            'name' => 'Test Document',
            'type' => DocumentType::KycDocument,
            'file_path' => 'documents/test.pdf',
            'is_active' => true,
        ]);

        $document->refresh();

        $this->assertEquals('test.pdf', $document->file_name);
        $this->assertNotNull($document->file_size);
        $this->assertNotNull($document->mime_type);
    }

    public function test_document_can_be_updated(): void
    {
        $document = Document::create([
            'name' => 'Original Name',
            'type' => DocumentType::Contract,
            'file_path' => 'documents/test.pdf',
            'is_active' => true,
        ]);

        $document->update([
            'name' => 'Updated Name',
            'is_active' => false,
        ]);

        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'name' => 'Updated Name',
            'is_active' => false,
        ]);
    }

    public function test_document_can_be_deleted(): void
    {
        $document = Document::create([
            'name' => 'Test Document',
            'type' => DocumentType::Other,
            'file_path' => 'documents/test.pdf',
            'is_active' => true,
        ]);

        $documentId = $document->id;
        $document->delete();

        $this->assertDatabaseMissing('documents', [
            'id' => $documentId,
        ]);
    }

    public function test_document_type_is_cast_to_enum(): void
    {
        $document = Document::create([
            'name' => 'Test',
            'type' => DocumentType::TaxForm,
            'file_path' => 'documents/test.pdf',
            'is_active' => true,
        ]);

        $this->assertInstanceOf(DocumentType::class, $document->type);
        $this->assertEquals('tax_form', $document->type->value);
    }

    public function test_document_is_active_casting(): void
    {
        $document = Document::create([
            'name' => 'Test',
            'type' => DocumentType::IdentityProof,
            'file_path' => 'documents/test.pdf',
            'is_active' => true,
        ]);

        $this->assertTrue($document->is_active);
        $this->assertIsBool($document->is_active);
    }

    public function test_all_document_types_are_available(): void
    {
        foreach (DocumentType::cases() as $type) {
            $document = Document::create([
                'name' => 'Document for '.$type->value,
                'type' => $type,
                'file_path' => 'documents/test.pdf',
                'is_active' => true,
            ]);

            $this->assertEquals($type, $document->type);
        }

        $this->assertCount(7, Document::all());
    }
}
