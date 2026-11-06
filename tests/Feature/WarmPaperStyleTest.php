<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\Article;
use App\Services\{LichtpuntIllustrationPrompt,ArticleVisualBrief,GeminiJson};
use Illuminate\Support\Facades\Http;
class WarmPaperStyleTest extends TestCase {
 protected function setUp():void {parent::setUp();Http::preventStrayRequests();}
 private function article(string $style):Article {
  $a=new Article;$a->setRawAttributes(['id'=>42,'source_title'=>'Solar panels on a house','en'=>json_encode(['title'=>'Solar panels on a house','body'=>['A positive energy development.']]),'nl'=>null,'photo'=>json_encode(['style'=>$style,'visual_brief'=>['scene'=>'A modest house with a gabled roof covered in solar panels','must_show'=>'Recognisable solar panels on the roof','avoid'=>'unrelated objects','people'=>false]])]);return $a;
 }
 public function test_previous_collage_versions_use_warm_recipe_without_falling_back_to_photography():void {
  $p=new LichtpuntIllustrationPrompt;
  foreach(['lichtpunt-torn-paper-v2','lichtpunt-colour-paper-v3','lichtpunt-warm-paper-v4',LichtpuntIllustrationPrompt::COLLAGE_STYLE] as $style){$a=$this->article($style);$this->assertSame(LichtpuntIllustrationPrompt::COLLAGE_STYLE,$p->style($a));$this->assertStringStartsWith('A modest house',$p->positive($a));$this->assertStringContainsString('warm brown and beige',$p->positive($a));$this->assertStringNotContainsString('Photorealistic editorial',$p->positive($a));}
 }
 public function test_generation_recipe_and_negative_prompt_are_consistent_with_approved_palette():void {
  $p=new LichtpuntIllustrationPrompt;$a=$this->article(LichtpuntIllustrationPrompt::COLLAGE_STYLE);$positive=$p->positive($a);$negative=$p->negative($a);
  $this->assertStringContainsString('many fine overlapping matte paper pieces',$positive);$this->assertStringContainsString('paper fibres',$positive);$this->assertStringContainsString('Solar panels: dark charcoal',$positive);$this->assertStringNotContainsString('coral, pink, blue',$positive);$this->assertStringNotContainsString('vivid full-colour',$positive);$this->assertStringNotContainsString('muted olive palette',$negative);$this->assertStringContainsString('electric blue scenery',$negative);$this->assertStringContainsString('photographic subject pasted on paper',$negative);Http::assertNothingSent();
 }
 public function test_editor_keeps_scene_neutral_and_review_checks_new_palette_and_fine_paper_detail():void {
  $a=$this->article(LichtpuntIllustrationPrompt::COLLAGE_STYLE);$brief=['scene'=>'A modest house with solar panels','must_show'=>'Solar panels','avoid'=>'unrelated objects','people'=>false,'confidence'=>.95];$review=['match'=>true,'cheerful'=>true,'paper'=>true,'confidence'=>.95,'reason'=>'Passende warme papierillustratie.'];$ai=$this->mock(GeminiJson::class);
  $ai->shouldReceive('ask')->once()->withArgs(function($system,$data,$schema){$this->assertStringContainsString('do not add decorative colours',$system);return true;})->andReturn($brief);
  $ai->shouldReceive('ask')->once()->withArgs(function($system,$data,$schema,$image){$this->assertStringContainsString('Tonal variety within this restrained palette is enough',$system);$this->assertStringContainsString('subject AND setting',$system);$this->assertStringContainsString('overall coherent paper impression',$system);$this->assertStringContainsString('unrelated stories',$system);return true;})->andReturn($review);
  $editor=app(ArticleVisualBrief::class);$plan=$editor->plan($a);$this->assertSame($brief['scene'],$plan['scene']);$this->assertTrue($editor->review($a,$plan,'fixture','image/png')['paper']);Http::assertNothingSent();
 }
 public function test_house_example_instructions_do_not_add_solar_panels_to_other_subjects():void {
  $p=new LichtpuntIllustrationPrompt;$a=$this->article(LichtpuntIllustrationPrompt::COLLAGE_STYLE);
  $photo=$a->photo;$photo['visual_brief']['scene']='Unbranded medicine pens and packaging on a clean surface';$photo['visual_brief']['must_show']='Medicine pens and packaging';$a->photo=$photo;
  $this->assertStringNotContainsString('Solar panels:',$p->positive($a));$this->assertStringContainsString('one or two small story-relevant accents',$p->positive($a));
 }

 public function test_provider_prompt_can_shorten_long_briefs_within_cloudflare_limit():void {
  $p=new LichtpuntIllustrationPrompt;$a=$this->article(LichtpuntIllustrationPrompt::COLLAGE_STYLE);$photo=$a->photo;$photo['visual_brief']['scene']=str_repeat('medicine ',55);$photo['visual_brief']['must_show']=str_repeat('medicine ',38);$a->photo=$photo;
  $this->assertLessThanOrEqual(2048,strlen($p->positive($a,450)));$this->assertLessThan(strlen($p->positive($a,450)),strlen($p->positive($a,100)));
 }

}
