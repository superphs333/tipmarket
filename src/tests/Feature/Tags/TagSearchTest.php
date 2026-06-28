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
        ->assertSee('name="tag_ids[]"', false);
});
