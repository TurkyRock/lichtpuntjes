<?php
namespace App\Support;

final class LocalAiJobLabel
{
    public static function metadata(array $payload): array
    {
        $data=$payload['data']??[];
        $properties=$payload['schema']['properties']??[];
        $kind=$payload['job_kind']??null;
        if(!in_array($kind,['moment','comment','story','news','article'],true)) {
            $kind=match(true) {
                isset($data['moment'])=>'moment',
                isset($data['comment'])=>'comment',
                isset($data['title'],$data['body'],$data['language'])=>'story',
                isset($data['title'],$data['description'])=>'news',
                isset($data['article'])||isset($data['title'],$data['paragraphs'])||isset($data['source_title'])=>'article',
                default=>'other',
            };
        }
        $stage=match(true) {
            isset($payload['image'])=>'image_review',
            isset($properties['scene'])||isset($properties['subject'],$properties['style'])=>'image_plan',
            isset($properties['source_language'])=>'translation',
            isset($properties['daily_score'])&&!isset($properties['decision'])=>'score',
            default=>'review',
        };
        return ['kind'=>$kind,'stage'=>$stage,'article_id'=>isset($payload['article_id'])?(int)$payload['article_id']:null];
    }
}
