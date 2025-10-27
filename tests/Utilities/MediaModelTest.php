<?php

namespace Carone\Media\Tests\Utilities;

use Carone\Media\Models\MediaResource;
use Carone\Media\Tests\TestCase;
use Carone\Media\Utilities\MediaModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;

class MediaModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset the MediaModel cache before each test
        MediaModel::reset();
    }

    protected function tearDown(): void
    {
        // Reset the MediaModel cache after each test
        MediaModel::reset();

        parent::tearDown();
    }

    public function test_get_class_returns_default_media_resource_when_no_config_set(): void
    {
        $this->assertEquals(MediaResource::class, MediaModel::getClass());
    }

    public function test_get_class_returns_configured_model_class(): void
    {
        // Create a test model class that extends MediaResource
        $testModel = new class extends MediaResource {
            protected $table = 'media_resources_new';
        };

        $testModelClass = get_class($testModel);

        config(['media.model' => $testModelClass]);

        $this->assertEquals($testModelClass, MediaModel::getClass());
    }

    public function test_get_class_caches_resolved_model(): void
    {
        $testModel = new class extends MediaResource {
            protected $table = 'media_resources_new';
        };

        $testModelClass = get_class($testModel);
        config(['media.model' => $testModelClass]);

        // First call should resolve and cache
        $firstCall = MediaModel::getClass();

        // Change config after first call
        config(['media.model' => MediaResource::class]);

        // Second call should return cached value, not new config
        $secondCall = MediaModel::getClass();

        $this->assertEquals($firstCall, $secondCall);
        $this->assertEquals($testModelClass, $secondCall);
    }

    public function test_reset_clears_cached_model(): void
    {
        $testModel = new class extends MediaResource {
            protected $table = 'media_resources';
        };

        $testModelClass = get_class($testModel);
        config(['media.model' => $testModelClass]);

        // First call caches the model
        MediaModel::getClass();

        // Change config and reset
        config(['media.model' => MediaResource::class]);
        MediaModel::reset();

        // Should now return the new config value
        $this->assertEquals(MediaResource::class, MediaModel::getClass());
    }

    public function test_make_creates_model_instance_without_saving(): void
    {
        $attributes = [
            'display_name' => 'test-file.jpg',
            'type' => 'image',
            'source' => 'local'
        ];

        $model = MediaModel::make($attributes);

        $this->assertInstanceOf(MediaResource::class, $model);
        $this->assertEquals('test-file.jpg', $model->display_name);
        $this->assertEquals('image', $model->type);
        $this->assertEquals('local', $model->source);
        $this->assertFalse($model->exists);
    }

    public function test_make_with_custom_model_class(): void
    {
        $testModel = new class extends MediaResource {
            protected $table = 'media_resources';
            protected $fillable = ['display_name', 'type', 'source', 'custom_field'];
        };

        $testModelClass = get_class($testModel);
        config(['media.model' => $testModelClass]);

        $attributes = ['display_name' => 'custom-test.jpg'];
        $model = MediaModel::make($attributes);

        $this->assertInstanceOf($testModelClass, $model);
        $this->assertEquals('custom-test.jpg', $model->display_name);
    }

    public function test_create_creates_and_saves_model_instance(): void
    {
        $attributes = [
            'display_name' => 'created-file.jpg',
            'type' => 'image',
            'source' => 'local',
            'path' => '/uploads/created-file.jpg',
            'disk' => 'local'
        ];

        $model = MediaModel::create($attributes);

        $this->assertInstanceOf(MediaResource::class, $model);
        $this->assertTrue($model->exists);
        $this->assertEquals('created-file.jpg', $model->display_name);
        $this->assertDatabaseHas('media_resources', [
            'display_name' => 'created-file.jpg',
            'type' => 'image',
            'source' => 'local'
        ]);
    }

    public function test_query_returns_query_builder(): void
    {
        $query = MediaModel::query();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $query);
        $this->assertEquals(MediaResource::class, $query->getModel()::class);
    }

    public function test_query_with_custom_model(): void
    {
        $testModel = new class extends MediaResource {
            protected $table = 'media_resources';
        };

        $testModelClass = get_class($testModel);
        config(['media.model' => $testModelClass]);

        $query = MediaModel::query();

        $this->assertEquals($testModelClass, $query->getModel()::class);
    }

    public function test_find_or_fail_returns_model_when_found(): void
    {
        // Create a test media record
        $media = MediaResource::create([
            'display_name' => 'findable-file.jpg',
            'type' => 'image',
            'source' => 'local',
            'path' => '/uploads/findable-file.jpg',
            'disk' => 'local'
        ]);

        $foundMedia = MediaModel::findOrFail($media->id);

        $this->assertInstanceOf(MediaResource::class, $foundMedia);
        $this->assertEquals($media->id, $foundMedia->id);
        $this->assertEquals('findable-file.jpg', $foundMedia->display_name);
    }

    public function test_find_or_fail_throws_exception_when_not_found(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        MediaModel::findOrFail(999999);
    }

    public function test_where_returns_query_builder_with_conditions(): void
    {
        // Create test media records
        MediaResource::create([
            'display_name' => 'image-file.jpg',
            'type' => 'image',
            'source' => 'local',
            'path' => '/uploads/image-file.jpg',
            'disk' => 'local'
        ]);

        MediaResource::create([
            'display_name' => 'video-file.mp4',
            'type' => 'video',
            'source' => 'local',
            'path' => '/uploads/video-file.mp4',
            'disk' => 'local'
        ]);

        $query = MediaModel::where('type', 'image');
        $results = $query->get();

        $this->assertCount(1, $results);
        $this->assertEquals('image-file.jpg', $results->first()->display_name);
    }

    public function test_where_with_multiple_parameters(): void
    {
        MediaResource::create([
            'display_name' => 'local-image.jpg',
            'type' => 'image',
            'source' => 'local',
            'path' => '/uploads/local-image.jpg',
            'disk' => 'local'
        ]);

        MediaResource::create([
            'display_name' => 'external-image.jpg',
            'type' => 'image',
            'source' => 'external',
            'url' => 'https://example.com/external-image.jpg'
        ]);

        $query = MediaModel::where('type', '=', 'image')->where('source', 'local');
        $results = $query->get();

        $this->assertCount(1, $results);
        $this->assertEquals('local-image.jpg', $results->first()->display_name);
    }

    public function test_validate_model_passes_for_valid_media_resource_subclass(): void
    {
        $testModel = new class extends MediaResource {
            protected $table = 'media_resources';
        };

        $testModelClass = get_class($testModel);

        // Should not throw any exception
        MediaModel::validateModel($testModelClass);

        $this->assertTrue(true); // If we reach here, validation passed
    }

    public function test_validate_model_throws_exception_for_non_existent_class(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Model class [NonExistentClass] does not exist.');

        MediaModel::validateModel('NonExistentClass');
    }

    public function test_validate_model_throws_exception_for_non_model_class(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Model class [stdClass] must extend Illuminate\Database\Eloquent\Model.');

        MediaModel::validateModel(\stdClass::class);
    }

    public function test_validate_model_throws_exception_for_model_not_extending_media_resource(): void
    {
        // Create a model that extends Model but not MediaResource
        $testModel = new class extends Model {
            protected $table = 'test_table';
        };

        $testModelClass = get_class($testModel);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Model class [{$testModelClass}] must extend Carone\Media\Models\MediaResource.");

        MediaModel::validateModel($testModelClass);
    }

    public function test_resolve_model_throws_exception_for_non_string_config(): void
    {
        config(['media.model' => 123]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Media model configuration must be a string class name.');

        MediaModel::getClass();
    }

    public function test_resolve_model_throws_exception_for_invalid_model_config(): void
    {
        config(['media.model' => 'InvalidModelClass']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Model class [InvalidModelClass] does not exist.');

        MediaModel::getClass();
    }

    public function test_all_methods_work_with_custom_model(): void
    {
        // Create a custom model that extends MediaResource
        $customModel = new class extends MediaResource {
            protected $table = 'media_resources';
            protected $fillable = ['display_name', 'type', 'source', 'path', 'disk', 'url', 'custom_field'];

            public function getCustomAttribute(): string
            {
                return 'custom_value';
            }
        };

        $customModelClass = get_class($customModel);
        config(['media.model' => $customModelClass]);

        // Test getClass
        $this->assertEquals($customModelClass, MediaModel::getClass());

        // Test make
        $madeModel = MediaModel::make(['display_name' => 'test.jpg']);
        $this->assertInstanceOf($customModelClass, $madeModel);

        // Test create
        $createdModel = MediaModel::create([
            'display_name' => 'custom-test.jpg',
            'type' => 'image',
            'source' => 'local',
            'path' => '/uploads/custom-test.jpg',
            'disk' => 'local'
        ]);
        $this->assertInstanceOf($customModelClass, $createdModel);
        $this->assertTrue($createdModel->exists);

        // Test findOrFail
        $foundModel = MediaModel::findOrFail($createdModel->id);
        $this->assertInstanceOf($customModelClass, $foundModel);
        $this->assertEquals($createdModel->id, $foundModel->id);

        // Test query
        $query = MediaModel::query();
        $this->assertEquals($customModelClass, $query->getModel()::class);

        // Test where
        $whereResults = MediaModel::where('display_name', 'custom-test.jpg')->get();
        $this->assertCount(1, $whereResults);
        $this->assertInstanceOf($customModelClass, $whereResults->first());
    }
}
