<?php
namespace App\Http\Controllers;
use App\Models\PositiveMoment;
use App\Services\{MomentImageFiles, MomentIllustrator};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Storage};
class MomentImageController extends Controller
{
    public function show(Request $request, PositiveMoment $moment) {
        $owner = $request->user() && $request->user()->id === $moment->user_id;
        $public = $moment->visibility === 'public' && $moment->status === 'approved' && $moment->user && !$moment->user->is_suspended;
        abort_unless(($owner || $public) && $moment->hasMomentImage(), 404);
        abort_unless(Storage::disk('local')->exists($moment->image_path), 404);
        return response()->file(Storage::disk('local')->path($moment->image_path), [
            'Cache-Control'=>'private, no-store, max-age=0', 'X-Robots-Tag'=>'noindex', 'X-Content-Type-Options'=>'nosniff',
        ]);
    }
    public function store(Request $request, PositiveMoment $moment) {
        abort_unless($request->user()->id === $moment->user_id, 403);
        $request->validate(['image_processing'=>'accepted']);
        $current = DB::transaction(function () use ($moment) {
            $m = PositiveMoment::whereKey($moment->id)->lockForUpdate()->firstOrFail();
            abort_unless($m->status === 'approved', 422);
            abort_if(in_array($m->image_state, ['queued','processing','waiting'], true) || $m->hasMomentImage(), 409);
            $m->update(['image_accepted_at'=>now(), 'image_state'=>'queued']);
            return $m;
        });
        MomentIllustrator::queue($current);
        return back()->with('status', app()->getLocale() === 'en' ? 'Your little image is on its way.' : 'Je kleine afbeelding komt eraan.');
    }
}
