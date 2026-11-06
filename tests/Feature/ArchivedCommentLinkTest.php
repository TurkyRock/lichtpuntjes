<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\{Article,Comment,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
class ArchivedCommentLinkTest extends TestCase
{
 use RefreshDatabase;
 public function test_hidden_article_has_no_clickable_link_but_comment_stays_visible():void
 {
  $u=User::forceCreate(['name'=>'Lezer','email'=>'archive@example.test','password'=>'Password12345','email_verified_at'=>now(),'community_accepted_at'=>now()]);
  $a=Article::create(['fingerprint'=>hash('sha256','archive'),'source_title'=>'Een vrolijke buurt','source_name'=>'Bron','source_url'=>'https://example.test/article','origin'=>'news','status'=>'published','nl'=>['title'=>'Een vrolijke buurt','body'=>['Een mooi verhaal.']]]);
  Comment::create(['user_id'=>$u->id,'article_id'=>$a->id,'status'=>'approved','body'=>'Wat een mooi initiatief!']);
  $this->actingAs($u)->get('/profiel?section=comments')->assertOk()->assertSee('href="/?story='.$a->id,false);
  $a->update(['hidden_at'=>now()]);
  $this->get('/profiel?section=comments')->assertOk()->assertSee('Wat een mooi initiatief!')->assertSee('Dit artikel staat niet meer op de site.')->assertDontSee('href="/?story='.$a->id,false);
 }
}
