<?php

namespace Tests\Feature\Documents;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;
use ZipArchive;

class DocumentUploadValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_supported_document_types_are_accepted(): void
    {
        Storage::fake('documents');

        $user = User::factory()->create();

        $documents = [
            UploadedFile::fake()->createWithContent(
                'تقرير 2026.pdf',
                "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF\n",
            ),
            $this->createDocxUpload('report.docx'),
            UploadedFile::fake()->createWithContent(
                'notes.txt',
                "A plain UTF-8 text document.\n",
            ),
        ];

        $this->actingAs($user);

        foreach ($documents as $document) {
            $this->postJson('/documents', [
                'document' => $document,
            ])->assertNoContent();
        }
    }

    private function createDocxUpload(string $name): UploadedFile
    {
        return $this->createZipUpload($name, [
            '[Content_Types].xml' => <<<'XML'
                <?xml version="1.0" encoding="UTF-8"?>
                <Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
                    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
                    <Default Extension="xml" ContentType="application/xml"/>
                    <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
                </Types>
                XML,
            '_rels/.rels' => <<<'XML'
                <?xml version="1.0" encoding="UTF-8"?>
                <Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
                    <Relationship
                        Id="rId1"
                        Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument"
                        Target="word/document.xml"
                    />
                </Relationships>
                XML,
            'word/document.xml' => <<<'XML'
                <?xml version="1.0" encoding="UTF-8"?>
                <w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
                    <w:body>
                        <w:p><w:r><w:t>Test document</w:t></w:r></w:p>
                    </w:body>
                </w:document>
                XML,
        ]);
    }

    /**
     * @param  array<string, string>  $entries
     */
    private function createZipUpload(string $name, array $entries): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'docx-');

        if ($path === false) {
            throw new RuntimeException('Unable to create the DOCX test file.');
        }

        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Unable to open the DOCX test archive.');
        }

        foreach ($entries as $entryName => $content) {
            $zip->addFromString($entryName, $content);
        }

        $zip->close();

        $content = file_get_contents($path);
        unlink($path);

        if ($content === false) {
            throw new RuntimeException('Unable to read the DOCX test file.');
        }

        return UploadedFile::fake()->createWithContent($name, $content);
    }

    public function test_mismatched_or_malformed_document_content_is_rejected(): void
    {
        $user = User::factory()->create();

        $documents = [
            UploadedFile::fake()->createWithContent(
                'disguised.txt',
                "%PDF-1.4\n%%EOF\n",
            ),
            $this->createZipUpload('broken.docx', [
                'payload.txt' => 'This ZIP is not a DOCX document.',
            ]),
        ];

        $this->actingAs($user);

        foreach ($documents as $document) {
            $this->postJson('/documents', [
                'document' => $document,
            ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('document');
        }

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_dangerous_original_filenames_are_rejected(): void
    {
        $user = User::factory()->create();

        $dangerousNames = [
            '../../report.pdf',
            'report.php.pdf',
            '<script>alert(1)</script>.pdf',
            '.hidden.pdf',
            '-option.pdf',
        ];

        $this->actingAs($user);

        foreach ($dangerousNames as $name) {
            $document = UploadedFile::fake()->createWithContent(
                $name,
                "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF\n",
            );

            $this->postJson('/documents', [
                'document' => $document,
            ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('document');
        }

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_document_exceeding_the_configured_size_limit_is_rejected(): void
    {
        config()->set('documents.upload.max_size_kilobytes', 1);

        $user = User::factory()->create();

        $document = UploadedFile::fake()->createWithContent(
            'large.pdf',
            "%PDF-1.4\n"
                .str_repeat('A', 2 * 1024)
                ."\n%%EOF\n",
        );

        $this->actingAs($user)
            ->postJson('/documents', [
                'document' => $document,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('document');

        $this->assertDatabaseCount('documents', 0);
    }
}
