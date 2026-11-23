<?php
namespace App\Services;

use Carbon\CarbonImmutable;
use RuntimeException;

class NewsDataRateLimitException extends RuntimeException
{
    public function __construct(public readonly CarbonImmutable $retryAt)
    {
        parent::__construct('NewsData HTTP 429.');
    }
}
