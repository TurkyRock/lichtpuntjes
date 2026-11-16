<?php
namespace App\Services;
class LocalAiPending extends AiWaitException {
 public function __construct(){parent::__construct(\Carbon\CarbonImmutable::now('UTC')->addSeconds(60),'local_queue');}
}
