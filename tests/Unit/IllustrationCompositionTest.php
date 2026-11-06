<?php
namespace Tests\Unit;

use App\Models\Article;
use App\Services\LichtpuntIllustrationPrompt;
use PHPUnit\Framework\TestCase;

class IllustrationCompositionTest extends TestCase
{
    private function article(string $title): Article
    {
        $a=new Article;
        $a->setRawAttributes(['id'=>42,'origin'=>'news','category'=>'people','source_title'=>$title,
            'en'=>json_encode(['title'=>$title,'teaser'=>'A positive development.','body'=>['A factual story.']]),
            'nl'=>null,'photo'=>null]);
        return $a;
    }

    public function test_medicine_research_and_nature_focus_on_the_subject_even_when_people_are_mentioned(): void
    {
        $prompt=new LichtpuntIllustrationPrompt;
        foreach(['New medicine helps children','Clinical research by volunteers','Solar batteries improve storage','Rare birds return to wetlands'] as $title){
            $a=$this->article($title);
            $this->assertSame('subject',$prompt->composition($a));
            $this->assertStringContainsString('no people, faces, hands',$prompt->positive($a));
            $this->assertStringStartsWith('people, person, human',$prompt->negative($a));
        }
    }

    public function test_human_stories_can_show_an_anonymous_activity_without_defaulting_to_portraits(): void
    {
        $prompt=new LichtpuntIllustrationPrompt;
        $a=$this->article('Volunteers help their neighbours');
        $this->assertSame('people',$prompt->composition($a));
        $this->assertStringContainsString('human-centred story',$prompt->positive($a));
        $this->assertStringNotContainsString('no people, faces, hands',$prompt->positive($a));
    }

    public function test_legacy_recipe_remains_available_for_retaining_existing_images(): void
    {
        $prompt=new LichtpuntIllustrationPrompt;
        $a=$this->article('New medicine helps children');
        $old=$prompt->legacyPositive($a);
        $this->assertNotSame($old,$prompt->positive($a));
        $this->assertStringNotContainsString('Composition requirement:',$old);
        $a->setAttribute('en',['title'=>'Different story','teaser'=>'New subject','body'=>['Changed.']]);
        $this->assertNotSame($old,$prompt->legacyPositive($a));
    }
}
