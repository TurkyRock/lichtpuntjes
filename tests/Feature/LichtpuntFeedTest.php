<?php
namespace Tests\Feature;
use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class LichtpuntFeedTest extends TestCase {
    use RefreshDatabase;
    public function test_only_published_bilingual_articles_are_public(): void {
        foreach (['pending','draft','error','rejected','insufficient','published'] as $status) {
            Article::create([
                'fingerprint'=>hash('sha256',$status),'source_title'=>'Original',
                'source_description'=>'Not public','source_name'=>'Source',
                'source_url'=>'https://example.org/'.$status,'status'=>$status,
                'category'=>'nature','published_at'=>'2026-10-10 10:00:00',
                'nl'=>['title'=>'Titel','teaser'=>'Intro','body'=>['Samenvatting']],
                'en'=>['title'=>'Title','teaser'=>'Intro','body'=>['Summary']],
            ]);
        }
        $this->getJson('/news/feed')->assertOk()->assertJsonCount(1,'articles')
            ->assertJsonPath('articles.0.url','https://example.org/published')
            ->assertJsonPath('articles.0.nl.title','Titel')
            ->assertJsonPath('articles.0.en.title','Title')
            ->assertJsonMissingPath('articles.0.source_description');
    }
    public function test_repeated_source_url_does_not_create_a_second_article(): void {
        $identity=['fingerprint'=>hash('sha256','https://example.org/story')];
        $fields=['source_title'=>'Title','source_name'=>'Source','source_url'=>'https://example.org/story'];
        Article::firstOrCreate($identity,$fields);
        Article::firstOrCreate($identity,$fields);
        $this->assertDatabaseCount('articles',1);
    }
}
