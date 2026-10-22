<?php
namespace App\Services;
use Illuminate\Support\Facades\Http;
use RuntimeException;
class GeminiJson {
 public function ask(string $system,array $data,array $schema,?array $image=null):array {
  $key=config('news.gemini_key');$model=config('news.gemini_model');
  if(!$key||!preg_match('~^[a-zA-Z0-9._-]+$~',(string)$model))throw new NewsServiceException('AI-configuratie ontbreekt: controleer GEMINI_API_KEY en GEMINI_MODEL.');
  $parts=[['text'=>json_encode($data,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)]];
  if($image!==null)$parts[]=['inlineData'=>['mimeType'=>$image['mime'],'data'=>base64_encode($image['bytes'])]];
  try{$r=Http::withOptions(['allow_redirects'=>false])->withHeaders(['x-goog-api-key'=>$key])->connectTimeout(8)->timeout(30)->post('https://generativelanguage.googleapis.com/v1beta/models/'.$model.':generateContent',[
   'systemInstruction'=>['parts'=>[['text'=>$system]]],'contents'=>[['role'=>'user','parts'=>$parts]],
   'generationConfig'=>['temperature'=>0.1,'responseMimeType'=>'application/json','responseSchema'=>$schema],
  ]);}catch(\Illuminate\Http\Client\ConnectionException $e){throw new NewsServiceException('Gemini-verbinding mislukt of time-out; controleer internet en HTTPS.',0,$e);}
  if(!$r->successful()){
   $hint=match($r->status()){400=>'ongeldig verzoek of modelconfiguratie',401,403=>'sleutel ongeldig of geen toegang',404=>'model niet gevonden',429=>'quota of snelheidslimiet bereikt',default=>'provider tijdelijk niet beschikbaar'};
   throw new NewsServiceException('Gemini HTTP '.$r->status().' ('.$hint.').');
  }
  if(!$r->successful()||$r->json('candidates.0.finishReason')!=='STOP')throw new NewsServiceException('Gemini leverde geen volledig antwoord; mogelijk blokkade of uitvoerlimiet.');
  $text=collect($r->json('candidates.0.content.parts',[]))->filter(fn($p)=>empty($p['thought']))->pluck('text')->implode('');
  try{$result=json_decode($text,true,512,JSON_THROW_ON_ERROR);}catch(\JsonException $e){throw new NewsServiceException('Gemini gaf ongeldige JSON.',0,$e);}if(!is_array($result))throw new NewsServiceException('Gemini gaf geen JSON-object.');return $result;
 }
}
