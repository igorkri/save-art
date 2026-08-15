<?php

namespace Tests\Feature\Services;

use App\Services\ImageProcessingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageProcessingServiceTest extends TestCase
{
    use RefreshDatabase;

    private ImageProcessingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ImageProcessingService;
        Storage::fake('public');
    }

    public function test_is_base64_image_returns_true_for_valid_base64(): void
    {
        $base64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $this->assertTrue($this->service->isBase64Image($base64));
    }

    public function test_is_base64_image_returns_false_for_regular_path(): void
    {
        $path = 'projects/covers/image.jpg';

        $this->assertFalse($this->service->isBase64Image($path));
    }

    public function test_is_base64_image_returns_false_for_null(): void
    {
        $this->assertFalse($this->service->isBase64Image(null));
    }

    public function test_is_base64_image_returns_false_for_empty_string(): void
    {
        $this->assertFalse($this->service->isBase64Image(''));
    }

    public function test_process_cover_saves_base64_as_file(): void
    {
        // 1x1 red pixel PNG in Base64
        $base64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

        $result = $this->service->processCover($base64);

        $this->assertNotNull($result);
        $this->assertStringStartsWith('projects/covers/', $result);
        $this->assertStringEndsWith('.png', $result);
        Storage::disk('public')->assertExists($result);
    }

    public function test_process_cover_returns_path_as_is_when_not_base64(): void
    {
        $path = 'projects/covers/existing-image.jpg';

        $result = $this->service->processCover($path);

        $this->assertEquals($path, $result);
    }

    public function test_process_cover_returns_null_for_empty_value(): void
    {
        $this->assertNull($this->service->processCover(null));
        $this->assertNull($this->service->processCover(''));
    }

    public function test_process_cover_deletes_old_cover_when_replacing(): void
    {
        // Створюємо стару обкладинку
        $oldCover = 'projects/covers/old-cover.jpg';
        Storage::disk('public')->put($oldCover, 'old image content');

        // 1x1 PNG
        $base64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

        $result = $this->service->processCover($base64, $oldCover);

        Storage::disk('public')->assertMissing($oldCover);
        Storage::disk('public')->assertExists($result);
    }

    public function test_process_content_blocks_converts_base64_images(): void
    {
        $base64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

        $contentBlocks = [
            [
                'type' => 'heading',
                'heading_level' => 'h2',
                'heading_text' => ['uk' => 'Заголовок', 'en' => 'Heading'],
            ],
            [
                'type' => 'paragraph',
                'paragraph_text' => ['uk' => 'Текст параграфа', 'en' => 'Paragraph text'],
            ],
            [
                'type' => 'image',
                'image' => $base64,
                'image_alt' => ['uk' => 'Опис', 'en' => 'Description'],
            ],
        ];

        $result = $this->service->processContentBlocks($contentBlocks);

        // Заголовок та параграф не змінюються
        $this->assertEquals('heading', $result[0]['type']);
        $this->assertEquals('paragraph', $result[1]['type']);

        // Зображення має бути конвертоване у шлях
        $this->assertEquals('image', $result[2]['type']);
        $this->assertStringStartsWith('projects/content-blocks/', $result[2]['image']);
        $this->assertStringEndsWith('.png', $result[2]['image']);
        Storage::disk('public')->assertExists($result[2]['image']);
    }

    /**
     * final_result: блок "gallery" зберігає масив зображень (а не одне 'image' —
     * та ж логіка конвертації Base64/очищення старих файлів має спрацьовувати
     * для кожного елемента масиву окремо.
     */
    public function test_process_content_blocks_converts_base64_images_in_gallery_block(): void
    {
        $base64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';
        $existingPath = 'projects/final-result/existing.jpg';

        $contentBlocks = [
            [
                'type' => 'gallery',
                'images' => [$base64, $existingPath],
            ],
        ];

        $result = $this->service->processContentBlocks($contentBlocks, null, 'projects/final-result');

        $this->assertStringStartsWith('projects/final-result/', $result[0]['images'][0]);
        $this->assertStringEndsWith('.png', $result[0]['images'][0]);
        Storage::disk('public')->assertExists($result[0]['images'][0]);
        $this->assertSame($existingPath, $result[0]['images'][1]);
    }

    /**
     * Зображення, видалене з галереї при редагуванні, має прибиратись з диска —
     * так само, як для одиничного 'image'-блока content_blocks.
     */
    public function test_process_content_blocks_deletes_removed_gallery_images(): void
    {
        $keptPath = 'projects/final-result/kept.jpg';
        $removedPath = 'projects/final-result/removed.jpg';
        Storage::disk('public')->put($keptPath, 'kept');
        Storage::disk('public')->put($removedPath, 'removed');

        $oldBlocks = [
            ['type' => 'gallery', 'images' => [$keptPath, $removedPath]],
        ];
        $newBlocks = [
            ['type' => 'gallery', 'images' => [$keptPath]],
        ];

        $this->service->processContentBlocks($newBlocks, $oldBlocks, 'projects/final-result');

        Storage::disk('public')->assertExists($keptPath);
        Storage::disk('public')->assertMissing($removedPath);
    }

    public function test_process_content_blocks_keeps_existing_paths(): void
    {
        $existingPath = 'projects/content-blocks/existing-image.jpg';

        $contentBlocks = [
            [
                'type' => 'image',
                'image' => $existingPath,
                'image_alt' => ['uk' => 'Опис'],
            ],
        ];

        $result = $this->service->processContentBlocks($contentBlocks);

        $this->assertEquals($existingPath, $result[0]['image']);
    }

    public function test_process_content_blocks_returns_null_for_null_input(): void
    {
        $this->assertNull($this->service->processContentBlocks(null));
    }

    public function test_process_content_blocks_returns_empty_array_for_empty_input(): void
    {
        $result = $this->service->processContentBlocks([]);
        $this->assertEquals([], $result);
    }

    public function test_save_base64_image_creates_file_with_correct_extension(): void
    {
        // JPEG image
        $jpegBase64 = 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/wAALCAABAAEBAREA/8QAFAABAAAAAAAAAAAAAAAAAAAACf/EABQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEAAD8AVN//2Q==';

        $result = $this->service->saveBase64Image($jpegBase64, 'test-images');

        $this->assertStringStartsWith('test-images/', $result);
        $this->assertStringEndsWith('.jpg', $result);
        Storage::disk('public')->assertExists($result);
    }

    public function test_save_base64_image_rejects_truncated_image_data(): void
    {
        // Валідний PNG-заголовок, але дані обірвані (немає IEND) —
        // base64_decode() таке пропускає, тож декодуємо через GD.
        $fullBase64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';
        $truncatedBase64 = substr($fullBase64, 0, -20);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Image data is corrupted or incomplete');

        $this->service->saveBase64Image($truncatedBase64, 'test-images');
    }
}
