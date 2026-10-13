<?php
namespace App\Console\Commands;
use App\Models\Comment;
use App\Services\CommentModerator;
use Illuminate\Console\Command;
class ReviewComments extends Command {
 protected $signature='comments:review {--limit=20} {--retry-errors : Probeer AI-storingen opnieuw}';
 protected $description='Controleer wachtende reacties en eventueel AI-storingen';
 public function handle(CommentModerator $m):int {$raw=(string)$this->option('limit');if(!ctype_digit($raw)||(int)$raw<1||(int)$raw>100){$this->error('--limit: 1 tot 100.');return self::FAILURE;}$q=Comment::whereNull('moderated_by')->where(function($q){$q->where('status','pending');if($this->option('retry-errors'))$q->orWhere(fn($q)=>$q->where('status','doubt')->where('ai_error',true));});foreach($q->oldest()->limit((int)$raw)->get() as $c){$m->review($c);$this->line('#'.$c->id.' '.$c->fresh()->status);}return self::SUCCESS;}
}
