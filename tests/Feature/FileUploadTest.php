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
    $disk = (string) config('flatpack.uploads.file_disk');
    Storage::fake($disk);
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
            ->and($json['files'][0]['disk'])->toBe($disk);
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack entity upload rejects file larger than effective max_size_kb', function () {
    Storage::fake((string) config('flatpack.uploads.file_disk'));
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
    max_size_kb: 2
YAML);
        config()->set('flatpack.composition.path', $tempPath);
        config()->set('flatpack.uploads.max_size_kb', 10240);

        /** @var User $user */
        $user = User::factory()->createOne();
        $file = UploadedFile::fake()->create('note.pdf', 50, 'application/pdf');

        $response = actingAs($user)->withHeaders([
            'Accept' => 'application/json',
        ])->post(route('flatpack.entities.upload', ['entity' => 'posts']), [
            'field' => 'attachment',
            'files' => [$file],
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['files']);
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack entity upload rejects second file when multiple is false', function () {
    Storage::fake((string) config('flatpack.uploads.file_disk'));
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
        $a = UploadedFile::fake()->create('a.pdf', 1, 'application/pdf');
        $b = UploadedFile::fake()->create('b.pdf', 1, 'application/pdf');

        $response = actingAs($user)->withHeaders([
            'Accept' => 'application/json',
        ])->post(route('flatpack.entities.upload', ['entity' => 'posts']), [
            'field' => 'attachment',
            'files' => [$a, $b],
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['files']);
    } finally {
        File::deleteDirectory($tempPath);
    }
});

test('flatpack entity upload rejects file not matching accept', function () {
    Storage::fake((string) config('flatpack.uploads.file_disk'));
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
    accept:
      - application/pdf
YAML);
        config()->set('flatpack.composition.path', $tempPath);

        /** @var User $user */
        $user = User::factory()->createOne();
        $png = UploadedFile::fake()->image('x.png');

        $response = actingAs($user)->withHeaders([
            'Accept' => 'application/json',
        ])->post(route('flatpack.entities.upload', ['entity' => 'posts']), [
            'field' => 'attachment',
            'files' => [$png],
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['files']);
    } finally {
        File::deleteDirectory($tempPath);
    }
});
