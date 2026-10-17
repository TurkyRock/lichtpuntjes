<?php
namespace Tests\Feature;

use App\Models\{Article,Comment,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentThreadsTest extends TestCase
{
 use RefreshDatabase;
 private function story():Article {return Article::create(['fingerprint'=>bin2hex(random_bytes(16)),'source_title'=>'Goed nieuws','source_name'=>'Bron','source_url'=>'https://example.com/news','status'=>'published','category'=>'nature','published_at'=>now(),'nl'=>['title'=>'Goed nieuws','body'=>['Mooi verhaal']],'en'=>['title'=>'Good news','body'=>['Happy story']]]);}
 private function author(bool $suspended=false):User {return User::forceCreate(['name'=>'Vriend','email'=>bin2hex(random_bytes(8)).'@example.com','password'=>'TestPassword123','is_suspended'=>$suspended]);}
 private function comment(Article $a,User $u,array $extra=[]):Comment {return Comment::create($extra+['article_id'=>$a->id,'user_id'=>$u->id,'status'=>'approved','body'=>'Een vriendelijk antwoord.']);}

 public function test_roots_and_replies_are_separate_with_visible_counts_and_nested_answers():void {
  $a=$this->story();$u=$this->author();$root=$this->comment($a,$u,['body'=>'Eerste reactie']);
  $reply=$this->comment($a,$u,['parent_id'=>$root->id,'body'=>'Eerste antwoord']);
  $nested=$this->comment($a,$u,['parent_id'=>$reply->id,'body'=>'Antwoord op antwoord']);
  $this->comment($a,$u,['parent_id'=>$root->id,'status'=>'doubt','body'=>'Geheime twijfel']);
  $this->comment($a,$this->author(true),['parent_id'=>$root->id,'body'=>'Geschorste auteur']);
  $this->getJson('/news/'.$a->id.'/comments')->assertOk()->assertJsonCount(1,'comments')->assertJsonPath('total',3)->assertJsonPath('comments.0.id',(string)$root->id)->assertJsonPath('comments.0.replies_count',1)->assertDontSee('Eerste antwoord')->assertDontSee('Geheime twijfel');
  $this->getJson('/news/'.$a->id.'/comments?parent_id='.$root->id)->assertOk()->assertJsonCount(1,'comments')->assertJsonPath('comments.0.id',(string)$reply->id)->assertJsonPath('comments.0.replies_count',1)->assertDontSee('Antwoord op antwoord');
  $this->getJson('/news/'.$a->id.'/comments?parent_id='.$reply->id)->assertOk()->assertJsonPath('comments.0.id',(string)$nested->id);
 }

 public function test_pagination_keeps_all_replies_reachable_under_their_parent():void {
  $a=$this->story();$u=$this->author();$root=$this->comment($a,$u);
  for($i=0;$i<21;$i++)$this->comment($a,$u,['parent_id'=>$root->id]);
  for($i=0;$i<20;$i++)$this->comment($a,$u);
  $this->getJson('/news/'.$a->id.'/comments?page=2')->assertOk()->assertJsonCount(1,'comments')->assertJsonPath('comments.0.id',(string)$root->id)->assertJsonPath('comments.0.replies_count',21)->assertJsonPath('total',42);
  $this->getJson('/news/'.$a->id.'/comments?parent_id='.$root->id.'&page=2')->assertOk()->assertJsonCount(1,'comments')->assertJsonPath('last_page',2)->assertJsonPath('items_total',21);
 }

 public function test_hidden_parents_are_not_disclosed_and_their_visible_answers_remain_reachable():void {
  $a=$this->story();$u=$this->author();$hidden=$this->comment($a,$u,['status'=>'rejected','body'=>'Verborgen inhoud']);$child=$this->comment($a,$u,['parent_id'=>$hidden->id]);
  $this->getJson('/news/'.$a->id.'/comments')->assertOk()->assertJsonPath('comments.0.id',(string)$child->id)->assertJsonPath('comments.0.parent',null)->assertDontSee('Verborgen inhoud');
  $this->getJson('/news/'.$a->id.'/comments?parent_id='.$hidden->id)->assertNotFound();
  $other=$this->story();$foreign=$this->comment($other,$u);
  $this->getJson('/news/'.$a->id.'/comments?parent_id='.$foreign->id)->assertNotFound();
  $a->update(['hidden_at'=>now()]);$this->getJson('/news/'.$a->id.'/comments')->assertNotFound();
 }
}
