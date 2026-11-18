<?php
namespace App\Services;
use App\Models\LocalAiJob;
use Illuminate\Support\Facades\DB;
class LocalAi {
 public function ask(string $system,array $data,array $schema,?array $image=null,?string $kind=null,?int $articleId=null):array {
  if(isset($data['__privacy_owner_id'])){
   $owners=array_unique(array_merge([(int)$data['__privacy_owner_id']],$data['__privacy_related_user_ids']??[]));sort($owners);
   $subjects=$data['__privacy_subjects']??[];
   $kind=$subjects[0]['kind']??$kind;
   unset($data['__privacy_owner_id'],$data['__privacy_related_user_ids'],$data['__privacy_subjects']);
   $answer=DB::transaction(function()use($owners,$subjects,$system,$data,$schema,$image,$kind,$articleId){
    foreach($owners as $owner)if(!\App\Models\User::whereKey($owner)->lockForUpdate()->first(['id']))throw new NewsServiceException('Account bestaat niet meer; geen AI-opdracht opgeslagen.');
    foreach($subjects as $subject){$class=match($subject['kind']){'comment'=>\App\Models\Comment::class,'moment'=>\App\Models\PositiveMoment::class,'story'=>\App\Models\LightspotSubmission::class,default=>throw new NewsServiceException('Onbekende bijdrage.')};if(!$class::whereKey($subject['id'])->lockForUpdate()->first(['id']))throw new NewsServiceException('Bijdrage is verwijderd; geen AI-opdracht opgeslagen.');}
    try{return $this->ask($system,$data,$schema,$image,$kind,$articleId);}catch(LocalAiPending $pending){return $pending;}
   });
   if($answer instanceof LocalAiPending)throw $answer;
   return $answer;
  }

  if($image!==null && (!isset($data['article'],$data['brief'])))throw new NewsServiceException('Profielfoto’s gaan in lokale modus naar handmatige controle.');
  $model=(string)config($image!==null?'local-ai.vision_model':'local-ai.model');
  if(!preg_match('/^[a-zA-Z0-9_.:-]{1,80}$/D',$model)||str_contains(strtolower($model),'cloud'))throw new NewsServiceException('Stel een lokaal model in, bijvoorbeeld qwen3:8b.');
  $payload=['system'=>$system,'data'=>$data,'schema'=>$schema];
  if($image!==null){if(!in_array($image['mime']??'', ['image/jpeg','image/png'],true)||!is_string($image['bytes']??null)||strlen($image['bytes'])>2000000)throw new NewsServiceException('Lokale beeldcontrole: afbeelding te groot of ongeldig.');$payload['image']=['mime'=>$image['mime'],'base64'=>base64_encode($image['bytes'])];}$json=json_encode($payload,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);
  if(strlen($json)>3000000)throw new NewsServiceException('Lokale AI-opdracht te groot; handmatige controle nodig.');
  $key=hash_hmac('sha256',$model."\n".$json,(string)config('app.key'));
  // Metadata is encrypted with the payload, excluded from the request key and model input.
  $payload['job_kind']=$kind;
  $payload['article_id']=$articleId;
  LocalAiJob::where('request_key',$key)->where('expires_at','<=',now())->delete();
  $job=LocalAiJob::firstOrCreate(['request_key'=>$key],['model'=>$model,'payload'=>$payload,'available_at'=>now(),'expires_at'=>now()->addHours((int)config('local-ai.retention_hours',24))]);
  if($articleId&&!($job->payload['article_id']??null))$job->update(['payload'=>array_merge($job->payload,['job_kind'=>$kind,'article_id'=>$articleId])]);
  if($job->status==='completed'){
   $result=$job->result;
   if(!is_array($result)||!LocalAiSchema::valid($result,$schema))throw new NewsServiceException('Lokaal antwoord heeft een ongeldig formaat.');
   // Model confidence is not calibrated evidence. Test mode requires a human for user moderation.
   if(config('local-ai.human_review')&&isset($result['confidence'])&&in_array('doubt',$schema['properties']['decision']['enum']??[],true)&&!str_contains($system,'HUMAN-approved')){
    $result['confidence']=0;
    if(isset($result['reason']))$result['reason']='Lokale AI-proefstand: een beheerder beoordeelt deze bijdrage.';
    if(isset($result['reason_nl']))$result['reason_nl']='Lokale AI-proefstand: een beheerder beoordeelt deze bijdrage.';
    if(isset($result['reason_en']))$result['reason_en']='Local AI trial mode: an administrator reviews this contribution.';
   }
   return $result;
  }
  if($job->status==='failed')throw new NewsServiceException('Lokale AI-opdracht mislukt. Controleer de laptop of laat handmatig beoordelen.');
  throw new LocalAiPending;
 }
 public function claim():?array {
  return DB::transaction(function(){
   $job=LocalAiJob::where('expires_at','>',now())->where('available_at','<=',now())->where(fn($q)=>$q->where('status','queued')->orWhere(fn($q)=>$q->where('status','processing')->where('lease_until','<=',now())))->orderBy('id')->lockForUpdate()->first();
   if(!$job)return null;
   if($job->claims>=(int)config('local-ai.max_claims',3)){$job->update(['status'=>'failed','lease_hash'=>null,'lease_until'=>null]);return null;}
   $lease=bin2hex(random_bytes(32));$job->update(['status'=>'processing','claims'=>$job->claims+1,'lease_hash'=>hash('sha256',$lease),'lease_until'=>now()->addSeconds((int)config('local-ai.lease_seconds',180))]);
   $payload=$job->payload;
   return ['id'=>$job->id,'lease'=>$lease,'model'=>$job->model,'system'=>$payload['system'],'data'=>$payload['data'],'schema'=>LocalAiSchema::convert($payload['schema']),'image'=>$payload['image']??null]+\App\Support\LocalAiJobLabel::metadata($payload);
  });
 }
 private function locked(int $id,string $lease,bool $completed=false):LocalAiJob {
  $j=LocalAiJob::whereKey($id)->lockForUpdate()->first();
  abort_unless($j&&$j->expires_at->isFuture()&&$j->lease_hash&&hash_equals($j->lease_hash,hash('sha256',$lease)),409);
  abort_unless(($completed&&$j->status==='completed')||($j->status==='processing'&&$j->lease_until?->isFuture()),409);
  return $j;
 }
 public function heartbeat(int $id,string $lease):array {
  return DB::transaction(function()use($id,$lease){$j=$this->locked($id,$lease);$j->update(['lease_until'=>now()->addSeconds((int)config('local-ai.lease_seconds',180))]);return ['saved'=>true];});
 }
 public function complete(int $id,string $lease,array $result):array {
  abort_if(strlen(json_encode($result,JSON_THROW_ON_ERROR))>200000,413);
  return DB::transaction(function()use($id,$lease,$result){
   $j=$this->locked($id,$lease,true);$hash=hash('sha256',json_encode($result,JSON_THROW_ON_ERROR));
   if($j->status==='completed'){abort_unless(hash_equals($j->completion_hash,$hash),409);return ['saved'=>true];}
   abort_unless(LocalAiSchema::valid($result,$j->payload['schema']),422,'Lokaal antwoord voldoet niet aan het schema.');
   $j->update(['status'=>'completed','result'=>$result,'completion_hash'=>$hash,'lease_until'=>null]);return ['saved'=>true];
  });
 }
 public function fail(int $id,string $lease):array {
  return DB::transaction(function()use($id,$lease){$j=$this->locked($id,$lease);$j->update(['status'=>$j->claims>=(int)config('local-ai.max_claims',3)?'failed':'queued','available_at'=>now()->addMinutes(2),'lease_until'=>null,'lease_hash'=>null]);return ['saved'=>true];});
 }
}
