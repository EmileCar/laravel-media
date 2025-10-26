<?php

namespace Carone\Media\Tests\ValueObjects;

use Carone\Media\Tests\TestCase;
use Carone\Media\ValueObjects\MediaType;
use Carone\Media\Strategies\AudioStrategy;
use Carone\Media\Strategies\DocumentStrategy;
use Carone\Media\Strategies\ImageStrategy;
use Carone\Media\Strategies\VideoStrategy;

class MediaTypeTest extends TestCase
{
    /** @test */
    public function it_has_all_expected_enum_cases(): void
    {
        $expectedCases = ['image', 'video', 'audio', 'document'];
        $actualCases = array_map(fn($case) => $case->value, MediaType::cases());

        $this->assertSame($expectedCases, $actualCases);
    }

    /** @test */
    public function it_can_be_created_from_string_values(): void
    {
        $this->assertSame(MediaType::IMAGE, MediaType::from('image'));
        $this->assertSame(MediaType::VIDEO, MediaType::from('video'));
        $this->assertSame(MediaType::AUDIO, MediaType::from('audio'));
        $this->assertSame(MediaType::DOCUMENT, MediaType::from('document'));
    }

    /** @test */
    public function it_throws_exception_for_invalid_string_values(): void
    {
        $this->expectException(\ValueError::class);
        MediaType::from('invalid');
    }

    /** @test */
    public function it_can_try_from_string_values(): void
    {
        $this->assertSame(MediaType::IMAGE, MediaType::tryFrom('image'));
        $this->assertSame(MediaType::VIDEO, MediaType::tryFrom('video'));
        $this->assertNull(MediaType::tryFrom('invalid'));
        $this->assertNull(MediaType::tryFrom(''));
        $this->assertNull(MediaType::tryFrom('IMAGE'));
    }

    /** @test */
    public function it_returns_correct_strategy_classes(): void
    {
        $this->assertSame(ImageStrategy::class, MediaType::IMAGE->getStrategyClass());
        $this->assertSame(VideoStrategy::class, MediaType::VIDEO->getStrategyClass());
        $this->assertSame(AudioStrategy::class, MediaType::AUDIO->getStrategyClass());
        $this->assertSame(DocumentStrategy::class, MediaType::DOCUMENT->getStrategyClass());
    }

    /** @test */
    public function it_returns_correct_human_readable_labels(): void
    {
        $this->assertSame('Image', MediaType::IMAGE->getLabel());
        $this->assertSame('Video', MediaType::VIDEO->getLabel());
        $this->assertSame('Audio', MediaType::AUDIO->getLabel());
        $this->assertSame('Document', MediaType::DOCUMENT->getLabel());
    }

    /** @test */
    public function it_returns_validation_rules_from_config(): void
    {
        // Test with configured validation rules
        $imageRules = MediaType::IMAGE->getValidationRules();
        $this->assertIsArray($imageRules);
        $this->assertContains('mimes:jpg,jpeg,png,gif', $imageRules);
        $this->assertContains('max:5120', $imageRules);

        $videoRules = MediaType::VIDEO->getValidationRules();
        $this->assertIsArray($videoRules);
        $this->assertContains('mimes:mp4,mov,avi', $videoRules);
        $this->assertContains('max:20480', $videoRules);
    }

    /** @test */
    public function it_returns_empty_array_for_unconfigured_validation_rules(): void
    {
        // Temporarily clear config
        config(['media.validation.image' => null]);

        $rules = MediaType::IMAGE->getValidationRules();
        $this->assertSame([], $rules);
    }

    /** @test */
    public function it_correctly_identifies_thumbnail_support(): void
    {
        $this->assertTrue(MediaType::IMAGE->supportsThumbnails());
        $this->assertFalse(MediaType::VIDEO->supportsThumbnails());
        $this->assertFalse(MediaType::AUDIO->supportsThumbnails());
        $this->assertFalse(MediaType::DOCUMENT->supportsThumbnails());
    }

    /** @test */
    public function it_checks_if_type_is_enabled_from_config(): void
    {
        // All types should be enabled by default config
        $this->assertTrue(MediaType::IMAGE->isEnabled());
        $this->assertTrue(MediaType::VIDEO->isEnabled());
        $this->assertTrue(MediaType::AUDIO->isEnabled());
        $this->assertTrue(MediaType::DOCUMENT->isEnabled());
    }

    /** @test */
    public function it_respects_disabled_types_in_config(): void
    {
        // Disable video and audio
        config(['media.enabled_types' => ['image', 'document']]);

        $this->assertTrue(MediaType::IMAGE->isEnabled());
        $this->assertFalse(MediaType::VIDEO->isEnabled());
        $this->assertFalse(MediaType::AUDIO->isEnabled());
        $this->assertTrue(MediaType::DOCUMENT->isEnabled());
    }

    /** @test */
    public function it_handles_empty_enabled_types_config(): void
    {
        config(['media.enabled_types' => []]);

        $this->assertFalse(MediaType::IMAGE->isEnabled());
        $this->assertFalse(MediaType::VIDEO->isEnabled());
        $this->assertFalse(MediaType::AUDIO->isEnabled());
        $this->assertFalse(MediaType::DOCUMENT->isEnabled());
    }

    /** @test */
    public function it_returns_correct_supported_mime_types(): void
    {
        $imageMimes = MediaType::IMAGE->getSupportedMimeTypes();
        $this->assertContains('image/jpeg', $imageMimes);
        $this->assertContains('image/png', $imageMimes);
        $this->assertContains('image/gif', $imageMimes);
        $this->assertContains('image/webp', $imageMimes);

        $videoMimes = MediaType::VIDEO->getSupportedMimeTypes();
        $this->assertContains('video/mp4', $videoMimes);
        $this->assertContains('video/quicktime', $videoMimes);

        $audioMimes = MediaType::AUDIO->getSupportedMimeTypes();
        $this->assertContains('audio/mpeg', $audioMimes);
        $this->assertContains('audio/wav', $audioMimes);

        $documentMimes = MediaType::DOCUMENT->getSupportedMimeTypes();
        $this->assertContains('application/pdf', $documentMimes);
        $this->assertContains('application/msword', $documentMimes);
    }

    /** @test */
    public function it_returns_correct_supported_extensions(): void
    {
        $imageExts = MediaType::IMAGE->getSupportedExtensions();
        $this->assertContains('jpg', $imageExts);
        $this->assertContains('jpeg', $imageExts);
        $this->assertContains('png', $imageExts);
        $this->assertContains('gif', $imageExts);
        $this->assertContains('webp', $imageExts);

        $videoExts = MediaType::VIDEO->getSupportedExtensions();
        $this->assertContains('mp4', $videoExts);
        $this->assertContains('mov', $videoExts);
        $this->assertContains('avi', $videoExts);

        $audioExts = MediaType::AUDIO->getSupportedExtensions();
        $this->assertContains('mp3', $audioExts);
        $this->assertContains('wav', $audioExts);

        $documentExts = MediaType::DOCUMENT->getSupportedExtensions();
        $this->assertContains('pdf', $documentExts);
        $this->assertContains('doc', $documentExts);
        $this->assertContains('docx', $documentExts);
        $this->assertContains('xls', $documentExts);
        $this->assertContains('xlsx', $documentExts);
    }

    /** @test */
    public function it_can_be_compared_for_equality(): void
    {
        $type1 = MediaType::IMAGE;
        $type2 = MediaType::IMAGE;
        $type3 = MediaType::VIDEO;

        $this->assertTrue($type1 === $type2);
        $this->assertFalse($type1 === $type3);
        $this->assertTrue($type1 !== $type3);
    }

    /** @test */
    public function it_can_be_used_in_match_expressions(): void
    {
        $getDescription = fn(MediaType $type) => match($type) {
            MediaType::IMAGE => 'Visual content',
            MediaType::VIDEO => 'Moving pictures',
            MediaType::AUDIO => 'Sound content',
            MediaType::DOCUMENT => 'Text content',
        };

        $this->assertSame('Visual content', $getDescription(MediaType::IMAGE));
        $this->assertSame('Moving pictures', $getDescription(MediaType::VIDEO));
        $this->assertSame('Sound content', $getDescription(MediaType::AUDIO));
        $this->assertSame('Text content', $getDescription(MediaType::DOCUMENT));
    }

    /** @test */
    public function it_can_be_used_in_arrays_and_collections(): void
    {
        $types = [MediaType::IMAGE, MediaType::VIDEO];

        $this->assertCount(2, $types);
        $this->assertContains(MediaType::IMAGE, $types);
        $this->assertContains(MediaType::VIDEO, $types);
        $this->assertNotContains(MediaType::AUDIO, $types);
    }
}