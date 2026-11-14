<?php
namespace App\Services;
class AiWaitException extends GeminiTemporaryException {
 public function __construct(public \Carbon\CarbonImmutable $retryAt,public string $reason){parent::__construct('De AI-controle wacht tijdelijk op nieuw tegoed of een nieuwe poging.');}
}
