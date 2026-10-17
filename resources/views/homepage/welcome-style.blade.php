<style id="paper-welcome-style">
.hero.daily-welcome.paper-welcome{display:block;grid-template-rows:none;min-height:0;height:auto;padding:7px 8px;margin:20px 0 16px;background:#edf3e9;border-radius:20px;position:relative;isolation:isolate}
.paper-welcome .welcome-paper{position:relative;isolation:isolate;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:18px;width:100%;min-width:0;max-width:none;min-height:280px;padding:48px 9%;text-align:center;float:none}
.welcome-paper::before{content:"";position:absolute;inset:0;z-index:-1;pointer-events:none;background-image:url('/images/lichtpunt-paper-welcome.png');background-image:image-set(url('/images/lichtpunt-paper-welcome.webp') type('image/webp'),url('/images/lichtpunt-paper-welcome.png') type('image/png'));background-position:center;background-size:100% 100%;background-repeat:no-repeat}
.paper-welcome #headline{font:600 clamp(34px,4vw,48px)/1.12 Georgia,serif;letter-spacing:-1.2px;color:#153e39;margin:0;position:relative;width:100%;max-width:none;padding:0;float:none;overflow-wrap:normal;text-wrap:balance}
.lp-title-word{position:relative;isolation:isolate;display:inline-block;padding:0 .10em;white-space:nowrap}
.lp-title-word::before{content:none}
.lp-title-i{position:relative;display:inline-block}
.lp-title-i::after{content:"";position:absolute;top:.045em;left:50%;transform:translateX(-50%);width:.27em;height:.27em;border:.055em solid #fffce4;border-radius:50%;background:#f7cb38;box-shadow:0 0 .16em .05em #fffbe8,0 0 .36em .14em #ffdc6888;pointer-events:none;z-index:2}
.lp-title-rays{position:absolute;top:.18em;left:50%;width:2em;height:2em;transform:translate(-50%,-50%);pointer-events:none;z-index:-1;background:repeating-conic-gradient(from 8deg,#d7a636b0 0deg 1deg,transparent 1deg 24deg);mask-image:radial-gradient(circle,transparent 0 15%,#000 23% 40%,transparent 65%);-webkit-mask-image:radial-gradient(circle,transparent 0 15%,#000 23% 40%,transparent 65%)}
.paper-welcome .welcome-subtitle{font:600 16px/1.5 system-ui,sans-serif;color:#153e39;width:100%;max-width:850px;margin:0}
.daily-welcome-actions{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));align-items:stretch;gap:12px;width:100%;max-width:590px;position:relative;z-index:1;margin:1px 0 0;padding:0}
.daily-welcome-actions :is(button,.daily-account){display:flex;align-items:center;justify-content:center;gap:7px;grid-column:auto;min-width:0;min-height:56px;border:0;border-radius:0;padding:13px 12px;text-decoration:none;font:600 13px/1.3 system-ui,sans-serif;text-align:center;position:relative;isolation:isolate;background:transparent;filter:drop-shadow(0 3px 2px #26453a40);transition:filter .15s ease,transform .15s ease}
.daily-welcome-actions img{width:18px;height:18px;flex:none}
.daily-welcome-actions :is(button,.daily-account)::before{content:"";position:absolute;inset:0;z-index:-1;pointer-events:none;background-color:var(--paper-color);background-image:url('/images/button-paper-cutout-v2.png');background-blend-mode:multiply;background-position:center;background-size:100% 130%;background-repeat:no-repeat;-webkit-mask:url('/images/button-paper-cutout-v2.png') center/100% 130% no-repeat;mask:url('/images/button-paper-cutout-v2.png') center/100% 130% no-repeat}
.daily-welcome-actions :nth-child(2)::before{transform:scaleX(-1)}
.daily-welcome-actions :nth-child(3)::before{transform:scaleY(-1)}
.daily-welcome-actions .daily-yellow{--paper-color:#ffdc68;color:#153e39}
.daily-welcome-actions .daily-yellow:hover{--paper-color:#ffe58b}
.daily-welcome-actions .daily-account{--paper-color:#00786e;color:#fff}
.daily-welcome-actions .daily-account:hover{--paper-color:#00665e}
.daily-welcome-actions :is(button,a):hover{filter:drop-shadow(0 4px 3px #26453a4d)}
.daily-welcome-actions :is(button,a):active{transform:translateY(1px)}
.daily-welcome-actions :is(button,a):focus-visible{outline:3px solid #153e39;outline-offset:4px}
.seo-detail .hero.daily-welcome.paper-welcome{display:none}
@media(min-width:701px){.paper-welcome #headline{transform:translateY(8px)}}
@media(prefers-reduced-motion:reduce){.daily-welcome-actions :is(button,a){transition:none}.daily-welcome-actions :is(button,a):active{transform:none}}
@media(forced-colors:active){.daily-welcome-actions :is(button,a){border:2px solid ButtonText;color:ButtonText;background:ButtonFace;filter:none}.daily-welcome-actions :is(button,a)::before{display:none}}
@media(max-width:950px){.paper-welcome .welcome-paper{padding:40px 12%;min-height:270px}.paper-welcome #headline{font-size:38px}}
@media(max-width:700px){.hero.daily-welcome.paper-welcome{padding:8px;margin:16px 0}.paper-welcome .welcome-paper{padding:28px 17px;min-height:285px;gap:15px}.welcome-paper::before{background-image:url('/images/hero-paper-strip.svg')}.paper-welcome #headline{font-size:32px;letter-spacing:-.8px}.paper-welcome .welcome-subtitle{font-size:14px}.daily-welcome-actions{gap:6px}.daily-welcome-actions :is(button,.daily-account){font-size:11px;padding:10px 7px;gap:5px}.daily-welcome-actions img{width:16px;height:16px}}
@media(max-width:380px){.daily-welcome-actions{grid-template-columns:1fr}.daily-welcome-actions :is(button,.daily-account){font-size:13px;min-height:48px}.paper-welcome .welcome-paper{padding:34px 17px}.paper-welcome #headline{font-size:30px}}
</style>
