<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\News;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class NewsApiPaginationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_multiple_news_items_can_belong_to_the_same_city(): void
    {
        Storage::fake('public');
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $city = City::query()->firstOrFail();
        $admin = User::query()->firstOrFail();
        $permission = Permission::query()->firstOrCreate([
            'name' => 'news-create',
            'guard_name' => 'web',
        ]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $admin->givePermissionTo($permission);
        $this->actingAs($admin);

        News::query()->where('city_id', $city->id)->delete();
        News::create([
            'city_id' => $city->id,
            'cover_image' => 'news/first-city-news.webp',
            'english_html' => '<p>First English news</p>',
            'urdu_html' => '<p>First Urdu news</p>',
            'created_by' => $admin->id,
        ]);

        $this->post(route('news.store'), [
            'city_id' => $city->id,
            'cover_image' => UploadedFile::fake()->image('second-city-news.webp'),
            'english_html' => '<p>Second English news</p>',
            'urdu_html' => '<p>Second Urdu news</p>',
        ])->assertRedirect(route('news.index'));

        $this->assertSame(2, News::query()->where('city_id', $city->id)->count());
    }

    public function test_news_list_is_paginated_ten_at_a_time(): void
    {
        News::query()->delete();
        $user = User::query()->firstOrFail();
        $cities = City::query()->limit(11)->get();
        $this->assertCount(11, $cities);

        foreach ($cities as $index => $city) {
            News::create([
                'city_id' => $city->id,
                'cover_image' => 'news/pagination-test-'.$index.'.webp',
                'english_html' => '<p>English news '.$index.'</p>',
                'urdu_html' => '<p>Urdu news '.$index.'</p>',
                'created_by' => $user->id,
                'created_at' => now()->addSeconds($index),
                'updated_at' => now()->addSeconds($index),
            ]);
        }

        $this->getJson('/api/news?page=1')
            ->assertOk()
            ->assertJsonPath('error', false)
            ->assertJsonPath('data.current_page', 1)
            ->assertJsonPath('data.per_page', 10)
            ->assertJsonPath('data.total', 11)
            ->assertJsonCount(10, 'data.data');

        $this->getJson('/api/news?page=2')
            ->assertOk()
            ->assertJsonPath('data.current_page', 2)
            ->assertJsonPath('data.per_page', 10)
            ->assertJsonCount(1, 'data.data');
    }

    public function test_news_id_still_returns_a_single_news_object(): void
    {
        $news = News::query()->firstOrFail();

        $this->getJson('/api/news?news_id='.$news->id)
            ->assertOk()
            ->assertJsonPath('error', false)
            ->assertJsonPath('data.id', $news->id)
            ->assertJsonMissingPath('data.current_page');
    }

    public function test_page_must_be_a_positive_integer(): void
    {
        $this->getJson('/api/news?page=0')
            ->assertOk()
            ->assertJsonPath('error', true)
            ->assertJsonPath('code', 102);
    }
}
