<style id="paper-welcome-style">
.hero.daily-welcome.paper-welcome{display:block;grid-template-rows:none;min-height:0;height:auto;padding:7px 8px;margin:12px 0 12px;background:#edf3e9;border-radius:20px;position:relative;isolation:isolate}
.paper-welcome .welcome-paper{position:relative;isolation:isolate;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;width:100%;min-width:0;max-width:none;min-height:170px;padding:23px 12%;text-align:center;float:none}
.welcome-paper::before{content:"";position:absolute;inset:0;z-index:-1;pointer-events:none;background-image:url('/images/lichtpunt-paper-welcome.png');background-image:image-set(url('/images/lichtpunt-paper-welcome.webp') type('image/webp'),url('/images/lichtpunt-paper-welcome.png') type('image/png'));background-position:center;background-size:100% 100%;background-repeat:no-repeat}
.paper-welcome #headline{font:600 clamp(30px,3.1vw,38px)/1.12 Georgia,serif;letter-spacing:-1.2px;color:#153e39;margin:0;position:relative;width:100%;max-width:none;padding:0;float:none;overflow-wrap:normal;text-wrap:balance}
.lp-title-word{position:relative;isolation:isolate;display:inline-block;padding:0 .10em;white-space:nowrap}
.lp-title-word::before{content:none}
.lp-title-i{position:relative;display:inline-block}
.lp-title-i::after{content:"";position:absolute;top:.045em;left:50%;transform:translateX(-50%);width:.27em;height:.27em;border:.055em solid #fffce4;border-radius:50%;background:#f7cb38;box-shadow:0 0 .16em .05em #fffbe8,0 0 .36em .14em #ffdc6888;pointer-events:none;z-index:2}
.lp-title-rays{position:absolute;top:.18em;left:50%;width:2em;height:2em;transform:translate(-50%,-50%);pointer-events:none;z-index:-1;background:repeating-conic-gradient(from 8deg,#d7a636b0 0deg 1deg,transparent 1deg 24deg);mask-image:radial-gradient(circle,transparent 0 15%,#000 23% 40%,transparent 65%);-webkit-mask-image:radial-gradient(circle,transparent 0 15%,#000 23% 40%,transparent 65%)}
.paper-welcome .welcome-subtitle{font:600 14px/1.45 system-ui,sans-serif;color:#153e39;width:100%;max-width:850px;margin:0}
.daily-welcome-actions{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));align-items:stretch;gap:12px;width:100%;max-width:660px;position:relative;z-index:1;margin:1px 0 0;padding:0}
.daily-welcome-actions :is(button,.daily-account){display:flex;align-items:center;justify-content:center;gap:7px;grid-column:auto;min-width:0;min-height:44px;border:0;border-radius:0;padding:9px 12px;text-decoration:none;font:600 13px/1.3 system-ui,sans-serif;text-align:center;position:relative;isolation:isolate;background:transparent;filter:drop-shadow(0 3px 2px #26453a40);transition:filter .15s ease,transform .15s ease}
.daily-welcome-actions svg,.daily-welcome-actions img{width:18px;height:18px;flex:none}
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

/* Keep the complete paper buttons and their shadows inside the torn paper. */
.paper-welcome .daily-welcome-actions{max-width:600px;padding:4px 6px;box-sizing:border-box}

@media(prefers-reduced-motion:reduce){.daily-welcome-actions :is(button,a){transition:none}.daily-welcome-actions :is(button,a):active{transform:none}}
@media(forced-colors:active){.daily-welcome-actions :is(button,a){border:2px solid ButtonText;color:ButtonText;background:ButtonFace;filter:none}.daily-welcome-actions :is(button,a)::before{display:none}}
@media(max-width:950px){.paper-welcome .welcome-paper{padding:24px 11%;min-height:180px}.paper-welcome #headline{font-size:32px}}
@media(max-width:700px){.hero.daily-welcome.paper-welcome{padding:6px;margin:10px 0}.paper-welcome .welcome-paper{padding:22px 12px;min-height:190px;gap:10px}.welcome-paper::before{background-image:url('/images/hero-paper-strip.svg')}.paper-welcome #headline{font-size:29px;letter-spacing:-.8px}.paper-welcome .welcome-subtitle{font-size:13px}.daily-welcome-actions{gap:6px}.daily-welcome-actions :is(button,.daily-account){font-size:11px;padding:9px 5px;gap:5px;min-height:48px}.daily-welcome-actions svg,.daily-welcome-actions img{width:16px;height:16px}}
@media(max-width:380px){.daily-welcome-actions :is(button,.daily-account){font-size:10px;gap:4px}.daily-welcome-actions svg,.daily-welcome-actions img{width:14px;height:14px}.paper-welcome #headline{font-size:27px}}
.paper-welcome .welcome-paper{padding-bottom:34px}
@media(max-width:700px){.paper-welcome .welcome-paper{padding:22px 20px 32px}}
/* Give the flower collage its original proportions instead of squeezing it. */
@media(min-width:701px){
 .paper-welcome .welcome-paper{aspect-ratio:16/3;min-height:175px;padding-top:18px;padding-bottom:22px}
 .paper-welcome .welcome-paper::before{background-size:cover}
}
@media(min-width:951px){.paper-welcome .welcome-paper{min-height:190px}}
/* Compact actions stay in the blank paper between the flower cutouts. */
.paper-welcome .daily-welcome-actions{grid-template-columns:1fr 1.15fr 1fr 1.4fr;width:74%;max-width:600px;gap:9px}
.daily-welcome-actions :nth-child(4)::before{transform:scaleX(-1)}
@media(min-width:701px){.paper-welcome .daily-welcome-actions :is(button,.daily-account){font-size:11px;padding:8px 5px;gap:5px;min-height:40px;white-space:nowrap}}
@media(min-width:701px){.daily-welcome-actions svg,.daily-welcome-actions img{width:15px;height:15px}}
@media(min-width:701px) and (max-width:950px){.paper-welcome .daily-welcome-actions{width:62%;grid-template-columns:repeat(2,minmax(0,1fr));max-width:360px}}
@media(max-width:700px){.paper-welcome .daily-welcome-actions{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;width:100%;max-width:480px}.paper-welcome .daily-welcome-actions :is(button,.daily-account){font-size:12px;min-height:44px}}
</style>
