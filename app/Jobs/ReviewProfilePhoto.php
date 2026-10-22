<?php
namespace App\Jobs;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\ProfilePhoto;
use App\Services\ProfilePhotoModerator;
class ReviewProfilePhoto implements ShouldQueue {
 use Queueable;
 public int $tries=2;
 public int $timeout=90;
 public function __construct(public int $photoId){$this->onConnection('database')->onQueue('lichtpuntjes');}
 public function handle(ProfilePhotoModerator $moderator):void {if($photo=ProfilePhoto::find($this->photoId))$moderator->review($photo);}
 public function failed(?\Throwable $e):void {ProfilePhoto::whereKey($this->photoId)->where('status','pending')->update(['status'=>'doubt','reason'=>'De fotocontrole is gestopt. De beheerder kijkt ernaar.']);}
}
