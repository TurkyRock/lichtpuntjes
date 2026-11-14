<?php
require __DIR__.'/beta.php';
require __DIR__.'/weather.php';
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Http\Controllers\NewsEngagementController as News;
use App\Http\Controllers\CommunityAuthController as Accounts;
use App\Http\Controllers\CommunityController as Community;
use App\Http\Controllers\CommunityAdminController as Admin;
use App\Http\Middleware\CommunityActive;
Route::middleware([\App\Http\Middleware\CommunityLocale::class,\App\Http\Middleware\CookiePrivacy::class])->group(function(){
Route::get('/momentjes',[\App\Http\Controllers\MomentController::class,'feed']);
Route::get('/profielfotos/{photo}',[\App\Http\Controllers\ProfilePhotoController::class,'show'])->whereNumber('photo')->middleware('throttle:120,1');
Route::get('/',[\App\Http\Controllers\PublicStoryController::class,'home']);
Route::get('/en',[\App\Http\Controllers\PublicStoryController::class,'home'])->defaults('locale','en');
Route::get('/nl',fn()=>redirect('/',301));
Route::get('/nl/onderwerp/{category}',[\App\Http\Controllers\PublicStoryController::class,'home'])->defaults('locale','nl')->whereIn('category',array_diff(array_keys(\App\Support\StorySeo::CATEGORIES),['all']));
Route::get('/en/topics/{category}',[\App\Http\Controllers\PublicStoryController::class,'home'])->defaults('locale','en')->whereIn('category',array_diff(array_keys(\App\Support\StorySeo::CATEGORIES),['all']));
Route::get('/nl/lichtpuntjes/{id}/{slug?}',[\App\Http\Controllers\PublicStoryController::class,'article'])->defaults('locale','nl')->whereNumber('id');
Route::get('/en/bright-spots/{id}/{slug?}',[\App\Http\Controllers\PublicStoryController::class,'article'])->defaults('locale','en')->whereNumber('id');
Route::get('/sitemap.xml',[\App\Http\Controllers\PublicStoryController::class,'sitemap']);
Route::get('/robots.txt',[\App\Http\Controllers\PublicStoryController::class,'robots']);
Route::post('/cookies/keuze',[\App\Http\Controllers\CookieChoiceController::class,'store'])->middleware('throttle:30,1,cookies:');
Route::get('/privacy',fn()=>view('community.privacy'));
Route::middleware('auth')->group(function(){
 Route::get('/privacy/verzoeken',[\App\Http\Controllers\PrivacyController::class,'index']);
 Route::post('/privacy/verzoeken',[\App\Http\Controllers\PrivacyController::class,'store'])->middleware('throttle:3,60,privacy:');
 Route::get('/beheer/privacy',[\App\Http\Controllers\PrivacyController::class,'admin']);
 Route::post('/beheer/privacy/{id}',[\App\Http\Controllers\PrivacyController::class,'resolve'])->whereNumber('id');
});
Route::get('/huisregels',fn()=>view(\App\Support\CommunityViews::name('rules')));
Route::get('/news/feed',[News::class,'index'])->middleware('throttle:120,1');
Route::get('/news/{article}/story',[News::class,'story'])->whereNumber('article');
Route::get('/news/{article}/comments',[Community::class,'comments'])->whereNumber('article')->middleware('throttle:120,1');
Route::post('/news/{article}/read',[News::class,'read'])->whereNumber('article')->middleware('throttle:120,1,news-read:');
// OAuth callbacks may return as an Apple cross-site POST. Only this endpoint is
// exempt from form CSRF: Socialite verifies state (and Apple verifies its nonce).
Route::match(['get','post'], '/auth/{provider}/callback', [\App\Http\Controllers\SocialAuthController::class,'callback'])
 ->whereIn('provider', ['google','apple','microsoft'])
 ->middleware(['guest','throttle:20,1,social-callback:'])
 ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);
Route::middleware('guest')->group(function(){
 Route::get('/auth/{provider}/redirect', [\App\Http\Controllers\SocialAuthController::class,'start'])->whereIn('provider',['google','apple','microsoft'])->middleware('throttle:10,1,social-start:');
 Route::get('/aanmelden/afronden', [\App\Http\Controllers\SocialAuthController::class,'form']);
 Route::post('/aanmelden/afronden', [\App\Http\Controllers\SocialAuthController::class,'complete'])->middleware('throttle:6,1,social-complete:');
 Route::get('/inloggen',fn()=>view(\App\Support\CommunityViews::name('auth'),['mode'=>'login']))->name('login');
 Route::post('/inloggen',[Accounts::class,'login'])->middleware('throttle:6,1');
 Route::get('/aanmelden',fn(Request $r)=>view(\App\Support\CommunityViews::name('auth'),['mode'=>'register','invite'=>preg_match('/^[a-f0-9]{32}$/',(string)$r->query('invite'))?$r->query('invite'):null]));
 Route::post('/aanmelden',[Accounts::class,'register'])->middleware('throttle:5,1');
 Route::get('/wachtwoord-vergeten',fn()=>view(\App\Support\CommunityViews::name('auth'),['mode'=>'forgot']))->name('password.request');
 Route::post('/wachtwoord-vergeten',[Accounts::class,'forgot'])->middleware('throttle:3,1')->name('password.email');
 Route::get('/reset-password/{token}',fn(Request $r,string $token)=>view(\App\Support\CommunityViews::name('auth'),['mode'=>'reset','token'=>$token,'email'=>$r->query('email')]))->name('password.reset');
 Route::post('/reset-password',[Accounts::class,'reset'])->middleware('throttle:6,1')->name('password.update');
});
Route::post('/uitloggen',[Accounts::class,'logout'])->middleware('auth');
Route::middleware(['auth',CommunityActive::class])->group(function(){
 Route::post('/lichtpuntjes/meldingen',[\App\Http\Controllers\LightpointNoticeController::class,'collect'])->middleware(['verified','throttle:12,1,lightpoint-notice:']);
 Route::get('/profiel',[Community::class,'profile']);
 Route::get('/lichtpuntje/delen',[\App\Http\Controllers\MomentController::class,'choose']);
 Route::get('/mijn-momentjes',[\App\Http\Controllers\MomentController::class,'index']);
 Route::post('/momentjes/{moment}/hart',[\App\Http\Controllers\MomentController::class,'heart'])->whereNumber('moment')->middleware(['verified','throttle:30,1']);
 Route::post('/momentjes',[\App\Http\Controllers\MomentController::class,'store'])->middleware(['verified','throttle:5,60,moment-create:']);
 Route::put('/momentjes/{moment}',[\App\Http\Controllers\MomentController::class,'update'])->whereNumber('moment')->middleware(['verified','throttle:8,60,moment-edit:']);
 Route::delete('/momentjes/{moment}',[\App\Http\Controllers\MomentController::class,'destroy'])->whereNumber('moment');
 Route::post('/beheer/momentjes/{moment}',[\App\Http\Controllers\MomentController::class,'moderate'])->whereNumber('moment')->middleware(['verified','throttle:30,1']);
 Route::post('/profiel/foto',[\App\Http\Controllers\ProfilePhotoController::class,'store'])->middleware(['verified','throttle:3,60,profile-photo:']);
 Route::delete('/profiel/foto',[\App\Http\Controllers\ProfilePhotoController::class,'destroy'])->middleware('throttle:10,1');
 Route::get('/profiel/foto/{photo}',[\App\Http\Controllers\ProfilePhotoController::class,'preview'])->whereNumber('photo');
 Route::get('/beheer/gebruikers',[\App\Http\Controllers\UserAdminController::class,'index'])->middleware('verified');
 Route::get('/beheer/gebruikers/{user}',[\App\Http\Controllers\UserAdminController::class,'show'])->whereNumber('user')->middleware('verified');
 Route::post('/beheer/profielfotos/{photo}',[\App\Http\Controllers\ProfilePhotoController::class,'moderate'])->whereNumber('photo')->middleware(['verified','throttle:30,1']);

 Route::post('/news/{article}/melden',[\App\Http\Controllers\LightspotController::class,'report'])->whereNumber('article')->middleware(['verified','throttle:3,60,lightspot-report:']);
 Route::get('/mijn-lichtpuntjes',[\App\Http\Controllers\LightspotController::class,'index']);
 Route::get('/lichtpuntje/schrijven',[\App\Http\Controllers\LightspotController::class,'create']);
 Route::post('/lichtpuntje/schrijven',[\App\Http\Controllers\LightspotController::class,'store'])->middleware(['verified','throttle:3,60,lightspot-create:']);
 Route::get('/mijn-lichtpuntjes/{submission}/aanpassen',[\App\Http\Controllers\LightspotController::class,'edit'])->whereNumber('submission');
 Route::post('/mijn-lichtpuntjes/{submission}/aanpassen',[\App\Http\Controllers\LightspotController::class,'update'])->whereNumber('submission')->middleware(['verified','throttle:3,60,lightspot-edit:']);
 Route::post('/mijn-lichtpuntjes/{submission}/herbeoordeling',[\App\Http\Controllers\LightspotController::class,'appeal'])->whereNumber('submission')->middleware(['verified','throttle:3,60,lightspot-appeal:']);
 Route::get('/beheer/lichtpuntjes',[\App\Http\Controllers\LightspotController::class,'admin'])->middleware('verified');
 Route::post('/beheer/lichtpuntjes/{submission}',[\App\Http\Controllers\LightspotController::class,'decide'])->whereNumber('submission')->middleware(['verified','throttle:30,1,lightspot-admin:']);

 Route::post('/comments/{comment}/revise',[Community::class,'revise'])->whereNumber('comment')->middleware(['verified','throttle:4,1,comment-revise:']);
 Route::post('/comments/{comment}/appeal',[Community::class,'appeal'])->whereNumber('comment')->middleware('throttle:5,1');
 Route::post('/huisregels/accepteren',[Community::class,'accept']);
 Route::get('/email/verify',fn()=>view(\App\Support\CommunityViews::name('verify')))->name('verification.notice');
 Route::get('/email/verify/{id}/{hash}',function(EmailVerificationRequest $r){$r->fulfill();return redirect('/profiel')->with('status','E-mail bevestigd. Laat het zonnetje maar schijnen!');})->middleware(['signed','throttle:6,1'])->name('verification.verify');
 Route::post('/email/verification-notification',[Accounts::class,'resend'])->middleware('throttle:3,1')->name('verification.send');
 Route::post('/news/{article}/like',[News::class,'like'])->whereNumber('article')->middleware('throttle:30,1');
 Route::post('/news/{article}/comments',[Community::class,'store'])->whereNumber('article')->middleware(['verified','throttle:4,1,comment-create:']);
 Route::post('/news/{article}/share',[Community::class,'share'])->whereNumber('article')->middleware(['verified','throttle:10,1,news-share:']);
 Route::post('/beheer/dagverhaal/anders',[\App\Http\Controllers\ArticleAdminController::class,'daily'])->middleware(['verified','throttle:12,1,daily-admin:']);
 Route::post('/beheer/dagverhaal/{article}',[\App\Http\Controllers\ArticleAdminController::class,'daily'])->whereNumber('article')->middleware(['verified','throttle:12,1,daily-admin:']);
 Route::get('/beheer/artikelen',[\App\Http\Controllers\ArticleAdminController::class,'index'])->middleware('verified');
 Route::post('/beheer/artikelen/{article}/zichtbaarheid',[\App\Http\Controllers\ArticleAdminController::class,'visibility'])->whereNumber('article')->middleware(['verified','throttle:60,1,article-admin:']);
 Route::get('/beheer',[\App\Http\Controllers\AdminWorkspaceController::class,'home'])->middleware('verified');
 Route::get('/beheer/reacties',[Admin::class,'index'])->middleware('verified');
 Route::get('/beheer/momentjes',[\App\Http\Controllers\AdminWorkspaceController::class,'moments'])->middleware('verified');
 Route::get('/beheer/profielfotos',[\App\Http\Controllers\AdminWorkspaceController::class,'photos'])->middleware('verified');
 Route::get('/beheer/donaties',[\App\Http\Controllers\AdminDonationsController::class,'index'])->middleware('verified');
 Route::post('/beheer/comments/{comment}',[Admin::class,'decide'])->whereNumber('comment')->middleware(['verified','throttle:60,1']);
 Route::post('/beheer/users/{user}',[Admin::class,'suspend'])->whereNumber('user')->middleware(['verified','throttle:20,1']);
});

});

require __DIR__.'/support.php';

require __DIR__.'/account-security.php';
require __DIR__.'/moment-images.php';
require __DIR__.'/article-collage.php';
require __DIR__.'/comment-conversations.php';

Route::get('/mijn-verwerking/{kind}/{id}',[\App\Http\Controllers\ContributionProgressController::class,'show'])->whereIn('kind',['story','moment'])->whereNumber('id')->middleware(['auth',\App\Http\Middleware\CommunityLocale::class,'throttle:120,1']);
