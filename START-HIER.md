# Lichtpuntjes — opgeschoonde projectversie

Gebaseerd op jouw aangeleverde `lichtpunt.zip` van 12 oktober 2026. De bestaande vormgeving, teksten en functies zijn behouden. Dit is het volledige bronproject, geen losse update. Composer installeert de vastgelegde bibliotheken; Node/Vite is niet nodig voor deze Blade-site.

## Overstappen met Herd

1. Stop lopende importcommando’s, de queueworker en de scheduler met Ctrl+C. Gebruik de oude site tijdens het overzetten niet voor nieuwe inzendingen.
2. Pak de ZIP uit in een **nieuwe map**, bijvoorbeeld `C:\Users\e_tur\Herd\lichtpunt-schoon`. Laat de bestaande map `lichtpunt` volledig staan. Pak dit pakket NIET over de oude map uit.
3. Open PowerShell in de nieuwe map en voer uit:

```powershell
Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass
.\Start-Lichtpunt.ps1 -OldProjectPath "C:\Users\e_tur\Herd\lichtpunt"
```

Het script installeert Composer-pakketten, kopieert lokaal je bestaande `.env` met dezelfde APP_KEY, maakt een consistente **SQLite-databasekopie**, neemt geldige nieuwsafbeeldingen en de actieve batch-/quotatellers over en migreert uitsluitend de kopie. Geen sleutels, gebruikersdatabase of gebruikersafbeeldingen zijn in dit downloadpakket opgenomen. Het script stopt als de nieuwe map al een `.env` bevat, zodat een tweede uitvoering niets overschrijft. Bij een afgebroken installatie: gebruik een nieuwe uitgepakte map.

4. Koppel in Herd het bestaande adres `lichtpunt.test` aan de **nieuwe projectmap**. De webroot hoort `public` te zijn. Laat de nieuwe projectmap op haar plaats staan: de gekopieerde database gebruikt een absoluut pad. Als Herd voorlopig een ander adres gebruikt, pas APP_URL en eventuele OAuth-callbacks daarop aan. De oude map niet hernoemen of verwijderen voordat je tevreden bent.
5. Open de site en vernieuw met Ctrl+F5. Controleer inloggen, artikelen, likes, account en beheer. Door beveiligde sessieopslag moet je mogelijk opnieuw inloggen.
6. Start vanuit de nieuwe map de worker:

```powershell
.\Start-Worker.ps1
```

Of rechtstreeks:

```powershell
php artisan queue:work database --queue=lichtpuntjes,default --timeout=1800 --tries=48 --sleep=3
```

Voor de bestaande automatische import elke drie uur moet daarnaast de scheduler draaien (`php artisan schedule:work` lokaal). Laat dit alleen draaien wanneer je automatisch nieuws wilt ophalen. Dit gebruikt quota van NewsData/Gemini/Cloudflare. Op hosting gebruik je een procesbeheerder voor de worker en een cronjob voor `schedule:run`.

## Terug naar de oude versie

Stop de nieuwe worker en scheduler en wijs Herd weer naar de oude projectmap. De originele database is niet gewijzigd door het overstapscript. Nieuwe activiteit in de schone versie staat alleen in de nieuwe database: die gaat niet automatisch terug naar het origineel. Laat beide versies nooit tegelijk importeren of inzendingen verwerken. Verwijder de oorspronkelijke map pas na een gecontroleerde back-up buiten de webroot.

## Behouden commando’s

- `php artisan news:import --pages=1 --limit=5`
- `php artisan news:import --process-only`
- `php artisan news:replace` (bestaande batch hervatten, niet automatisch gestart)
- `php artisan news:review 1`
- `php artisan news:illustrations --help`
- `php artisan news:photos --help` (bestaand compatibiliteitscommando)
- `php artisan lichtpuntjes:controle`
- `php artisan lichtpuntjes:hervat --limit=5`
- `php artisan lichtpunt:social-check`

Gebruik `php artisan list` voor alle opdrachten. Een lopende `news:replace`-batch wordt dankzij het meegenomen statusbestand niet opnieuw begonnen.

## Beveiliging en publieke hosting

- Het pakket heeft geen echte `.env`. Het overstapscript verwerkt deze uitsluitend lokaal. APP_KEY nooit opnieuw genereren voor bestaande data.
- APP_DEBUG wordt false, sessies worden versleuteld en logs roteren. Bewaartermijnen voor gebruikersdata zijn hiermee nog niet geregeld.
- Zet op hosting APP_ENV=production, APP_DEBUG=false, APP_URL op HTTPS en SESSION_SECURE_COOKIE=true. Gebruik alleen `public` als webroot, niet de projectroot. Stel vertrouwde proxy’s alleen voor jouw echte hosting in.
- Zorg dat alleen de benodigde procesgebruiker naar storage en bootstrap/cache kan schrijven. Houd databases, back-ups en sleutels buiten public.
- Composer: `composer install --no-dev --optimize-autoloader`, daarna `php artisan config:cache`, `php artisan route:cache`, `php artisan view:cache`.
- Google/Apple/Microsoft vragen nog steeds eigen correcte credentials/callbacks; de bijbehorende bibliotheken zijn nu vastgelegd in composer.lock. Deze logins zijn met mocks getest, niet met jouw externe accounts.
- Cloudflare Free blijft de standaard; ComfyUI is beschikbaar als lokale optie. Er wordt niet automatisch overgeschakeld naar een betaalde dienst. De accountquota van Cloudflare blijven bepalend.
- Gebruik voor echte e-mail een werkende mailconfiguratie. MAIL_MAILER=log is alleen geschikt voor lokale tests en schrijft mailinhoud naar logs.
- De privacyverklaring blijft concept zolang bewaartermijnen, leveranciersafspraken en de definitieve grondslagen niet zijn afgerond. Privacyverzoeken blijven handmatig te behandelen.
- Deze review is geen garantie dat elk denkbaar beveiligingsprobleem is uitgesloten. Hosting, actuele afhankelijkheden en misbruikmonitoring vragen blijvend onderhoud.

## Tests

Tests gebruiken afgedwongen SQLite `:memory:` met een testsleutel; nooit je echte database. Uitvoeren: `php artisan test`. Frontendcontroles: `node tests/Frontend/community.cjs` (Node alleen nodig voor deze tests).

Zie WIJZIGINGEN.md voor uitgevoerde controles en opruimingen.
