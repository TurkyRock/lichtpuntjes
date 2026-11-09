<?php
namespace App\Services;
use App\Models\{User, PositiveMoment, LightspotSubmission};
class PublicationRevisions
{
    public function forUser(User $user): array
    {
        return [
            'moments' => PositiveMoment::where('user_id', $user->id)->where('visibility', 'public')->where('status', 'rejected')->count(),
            'stories' => LightspotSubmission::where('user_id', $user->id)->where('status', 'rejected')->count(),
        ];
    }
}
