<?php

use App\Livewire\Tags\TagSelector;
use App\Models\Tag;
use App\Services\Tags\TagSearchService;
use Livewire\Livewire;

test('tag search service returns active matching tags', function () {
    $matchingTag = Tag::query()->create([
        'name' => 'Laravel',
        'slug' => 'laravel',
        'usage_count' => 5,
        'is_active' => true,
    ]);

    Tag::query()->create([
        'name' => 'Livewire',
        'slug' => 'livewire',
        'usage_count' => 10,
        'is_active' => true,
    ]);

    Tag::query()->create([
        'name' => 'Laravel Deprecated',
        'slug' => 'laravel-deprecated',
        'is_active' => false,
    ]);

    $results = app(TagSearchService::class)->search('lara');

    expect($results)->toHaveCount(1)
        ->and($results->first()->is($matchingTag))->toBeTrue();
});

test('livewire tag selector searches and selects tags', function () {
    $tag = Tag::query()->create([
        'name' => 'Laravel',
        'slug' => 'laravel',
        'is_active' => true,
    ]);

    Livewire::test(TagSelector::class)
        ->set('query', 'lara')
        ->call('search')
        ->assertSee('Laravel')
        ->call('addTag', $tag->id)
        ->assertSet('selectedTags.0.id', $tag->id)
        ->assertSet('value', ['Laravel'])
        ->assertSee('name="tag_names[]"', false);
});

test('livewire tag selector adds a new tag candidate without creating a tag record', function () {
    Livewire::test(TagSelector::class)
        ->set('query', '욕실정리')
        ->call('search')
        ->assertSee('새 태그로 추가')
        ->call('addNewTag')
        ->assertSet('selectedTags.0.name', '욕실정리')
        ->assertSet('selectedTags.0.isNew', true)
        ->assertSet('value', ['욕실정리'])
        ->assertSee('aria-label="신규 태그"', false)
        ->assertSee('name="tag_names[]"', false);

    expect(Tag::query()->where('name', '욕실정리')->exists())->toBeFalse();
});

test('livewire tag selector can sync selected tag names for ai flows', function () {
    $tag = Tag::query()->create([
        'name' => '청소',
        'slug' => '청소',
        'is_active' => true,
    ]);

    Livewire::test(TagSelector::class)
        ->call('addTag', $tag->id)
        ->set('query', '#욕실 정리')
        ->call('search')
        ->call('addNewTag')
        ->assertSet('value', ['청소', '욕실정리']);
});
