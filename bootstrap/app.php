<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [\App\Http\Middleware\SecurityHeaders::class,\App\Http\Middleware\AccountSessionSecurity::class,\App\Http\Middleware\RecentAdminConfirmation::class,\App\Http\Middleware\RecordAccountVisit::class,\App\Http\Middleware\BetaGate::class]);
        $middleware->prependToPriorityList(\Illuminate\Routing\Middleware\SubstituteBindings::class, \App\Http\Middleware\AccountSessionSecurity::class);
        $middleware->prependToPriorityList(\Illuminate\Routing\Middleware\SubstituteBindings::class, \App\Http\Middleware\RecentAdminConfirmation::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Illuminate\Http\Exceptions\ThrottleRequestsException $e, \Illuminate\Http\Request $request) {
            if ($request->is('inloggen') && $request->isMethod('post')) {
                $seconds=max(1,(int)($e->getHeaders()['Retry-After']??60));
                $en=$request->query('lang',app()->getLocale())==='en';
                $message=$en?'Too many sign-in attempts. Please try again in '.$seconds.' seconds.':'Te veel inlogpogingen. Probeer het over '.$seconds.' seconden opnieuw.';
                if($request->expectsJson())return response()->json(['message'=>$message,'retry_after'=>$seconds],429,$e->getHeaders());
                return redirect('/inloggen?lang='.($en?'en':'nl'))->withErrors(['email'=>$message])->withInput($request->only('email'))->withHeaders($e->getHeaders());
            }
            if (!$request->is('news/*/comments','comments/*/reply','comments/*/heart')) return null;
            $seconds=max(1,(int)($e->getHeaders()['Retry-After']??60));
            $en=$request->query('lang',app()->getLocale())==='en';
            $message=$en?'A little pause: please try again in '.$seconds.' seconds. Your text is still here.':'Even een korte pauze: probeer het over '.$seconds.' seconden opnieuw. Je tekst blijft staan.';
            if($request->expectsJson())return response()->json(['message'=>$message,'retry_after'=>$seconds],429,$e->getHeaders());
            return back()->withErrors(['body'=>$message])->withInput($request->only('body','reply_root'));
        });
        $exceptions->dontFlash(['password','password_confirmation','current_password','email_password','existing_password','token','code']);
    })->create();
