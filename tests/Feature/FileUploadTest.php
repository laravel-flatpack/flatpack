<?php

declare(strict_types=1);

use Flatpack\Tests\Models\User;
use Flatpack\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

uses(TestCase::class, RefreshDatabase::class);

test('flatpack entity upload stores files and returns metadata json', function () {
    Storage::fake('public');
    $tempPath = sys_get_temp_dir() . '/flatpack-upload-' . uniqid('', true);

    try {
        File::ensureDirectoryExists($tempPath . '/posts');
        File::put($tempPath . '/posts/form.yaml', <<<'YAML'
name: Posts
model: Flatpack\Tests\Models\Post
fields:
  attachment:
    id: attachment
    label: Attachment
    type: file-upload
YAML);
        config()->set('flatpack.composition.path', $tempPath);

        /** @var User $user */
        $user = User::factory()->createOne();
        $file = UploadedFile::fake()->create('note.pdf', 120, 'application/pdf');

        $response = actingAs($user)->post(
            route('flatpack.entities.upload', ['entity' => 'posts']),
            [
                'field' => 'attachment',
                'files' => [$file],
            ],
        );

        $response->assertOk();
        $response->assertJsonStructure(['files', 'multiple', 'mode']);
        $response->assertJsonPath('multiple', false);
        $response->assertJsonPath('mode', 'url');
        $json = $response->json();
        expect($json['files'])->toHaveCount(1)
            ->and($json['files'][0]['name'])->toBe('note.pdf')
            ->and($json['files'][0]['disk'])->toBe('public');
    } finally {
        File::deleteDirectory($tempPath);
    }
});
