<?php
namespace App\Services;
use App\Models\User;
use Illuminate\Support\Facades\{Crypt,File};
class DeletionLedger {
 public function path():string{return storage_path('app/private/privacy-deletions.enc');}
 public function record(User $u,bool $keep):void {
  File::ensureDirectoryExists(dirname($this->path()));
  $record=['id'=>$u->id,'email_hash'=>DetachedSupport::emailHash($u->email),'created_at'=>$u->created_at->toIso8601String(),'deleted_at'=>now()->toIso8601String(),'keep_support'=>$keep];
  $line=Crypt::encryptString(json_encode($record,JSON_THROW_ON_ERROR))."\n";
  if(file_put_contents($this->path(),$line,FILE_APPEND|LOCK_EX)===false)throw new \RuntimeException('Verwijderregister opslaan mislukt.');@chmod($this->path(),0600);
 }
}
