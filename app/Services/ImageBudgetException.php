<?php
namespace App\Services;
class ImageBudgetException extends NewsServiceException
{
    public function retryAfter(): int
    {
        return max(60, (int) now('UTC')->diffInSeconds(now('UTC')->addDay()->startOfDay()->addMinute()));
    }
}
