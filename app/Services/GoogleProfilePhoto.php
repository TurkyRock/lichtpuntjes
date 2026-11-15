<?php
namespace App\Services;

use App\Jobs\ReviewProfilePhoto;
use App\Models\ProfilePhoto;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GoogleProfilePhoto
{
    public function import(User $user, array $identity): bool
    {
        if (($identity['provider'] ?? '') !== 'google' || $user->is_suspended || $user->profile_photo_id || $user->photos()->exists()) return false;
        $url = $identity['avatar'] ?? '';
        if (!is_string($url) || strlen($url) > 2048) return false;
        $parts = parse_url($url);
        if (!$parts || ($parts['scheme'] ?? '') !== 'https' || !preg_match('/^lh[0-9]+\.googleusercontent\.com$/D', $parts['host'] ?? '') || isset($parts['user']) || isset($parts['pass']) || isset($parts['fragment']) || isset($parts['port'])) return false;

        $response = Http::connectTimeout(2)->timeout(5)->withOptions(['stream' => true, 'read_timeout' => 5, 'allow_redirects' => false])->get($url);
        if (!$response->successful() || (int)$response->header('Content-Length') > 2097152) return false;
        $stream = $response->toPsrResponse()->getBody();
        $bytes = '';
        try {
            while (!$stream->eof() && strlen($bytes) <= 2097152) {
                $chunk = $stream->read(min(65536, 2097153 - strlen($bytes)));
                if ($chunk === '') break;
                $bytes .= $chunk;
            }
        } finally { $stream->close(); }
        if (!$bytes || strlen($bytes) > 2097152) return false;
        $size = @getimagesizefromstring($bytes);
        if (!$size || !in_array($size[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true) || $size[0] > 2400 || $size[1] > 2400 || $size[0] * $size[1] > 4000000) return false;

        $temporary = tempnam(sys_get_temp_dir(), 'lichtpunt-google-');
        if ($temporary === false) return false;
        try {
            file_put_contents($temporary, $bytes);
            $jpeg = app(PortraitNormalizer::class)->jpeg(new UploadedFile($temporary, 'google-profile', null, null, true));
        } finally { @unlink($temporary); }
        $path = 'profile-photos/'.Str::uuid().'.jpg';
        if (!Storage::disk('local')->put($path, $jpeg)) return false;
        try {
            $photo = DB::transaction(function () use ($user, $path) {
                $locked = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
                // Also preserve removed/rejected photos: signing in must not undo that choice.
                if ($locked->is_suspended || $locked->profile_photo_id || $locked->photos()->exists()) return null;
                return ProfilePhoto::create(['user_id' => $locked->id, 'path' => $path, 'status' => 'pending', 'self_confirmed_at' => now(), 'reason' => 'Overgenomen van het gekoppelde Google-account; wacht op fotocontrole.']);
            });
        } catch (\Throwable $e) { Storage::disk('local')->delete($path); throw $e; }
        if (!$photo) { Storage::disk('local')->delete($path); return false; }
        try { ReviewProfilePhoto::dispatch($photo->id); }
        catch (\Throwable $e) { $photo->update(['status' => 'doubt', 'reason' => 'Google-foto ontvangen; de beheerder kijkt ernaar omdat de controle niet kon starten.']); }
        return true;
    }
}
