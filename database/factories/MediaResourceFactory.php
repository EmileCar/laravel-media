<?php

namespace Database\Factories;

use Carone\Media\Models\MediaResource;
use Illuminate\Database\Eloquent\Factories\Factory;

class MediaResourceFactory extends Factory
{
    protected $model = MediaResource::class;

    public function definition()
    {
        return [
            'type' => $this->faker->randomElement(['image', 'video', 'audio', 'document']),
            'source' => $this->faker->randomElement(['local', 'external']),
            'path' => $this->faker->filePath(),
            'url' => $this->faker->optional()->url(),
            'display_name' => $this->faker->words(3, true),
            'description' => $this->faker->optional()->sentence(),
            'date' => $this->faker->date(),
            'meta' => [
                'original_name' => $this->faker->word() . '.jpg',
                'size' => $this->faker->numberBetween(1000, 1000000),
                'mime_type' => $this->faker->mimeType(),
            ],
            'thumbnail_path' => $this->faker->optional()->filePath(),
            'thumbnail_url' => $this->faker->optional()->url(),
        ];
    }

    public function local()
    {
        return $this->state(function (array $attributes) {
            return [
                'source' => 'local',
                'url' => null,
            ];
        });
    }

    public function external()
    {
        return $this->state(function (array $attributes) {
            return [
                'source' => 'external',
                'path' => null,
                'url' => $this->faker->url(),
            ];
        });
    }

    public function image()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'image',
                'meta' => [
                    'original_name' => $this->faker->firstName() . '.jpg',
                    'size' => $this->faker->numberBetween(50000, 2000000),
                    'mime_type' => 'image/jpeg',
                ],
            ];
        });
    }

    public function video()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'video',
                'meta' => [
                    'original_name' => $this->faker->firstName() . '.mp4',
                    'size' => $this->faker->numberBetween(1000000, 100000000),
                    'mime_type' => 'video/mp4',
                ],
            ];
        });
    }

    public function audio()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'audio',
                'meta' => [
                    'original_name' => $this->faker->firstName() . '.mp3',
                    'size' => $this->faker->numberBetween(500000, 10000000),
                    'mime_type' => 'audio/mpeg',
                ],
            ];
        });
    }

    public function document()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'document',
                'meta' => [
                    'original_name' => $this->faker->firstName() . '.pdf',
                    'size' => $this->faker->numberBetween(100000, 5000000),
                    'mime_type' => 'application/pdf',
                ],
            ];
        });
    }
}
