# Controle en wijzigingen — 12 oktober 2026

## Uitgangspunt

De door Erik aangeleverde, teruggedraaide ZIP is de basis. De homepage, stijlen, kleuren, teksten en pagina-indeling zijn niet opnieuw ontworpen. Alle 30 bestanden onder public/css en public/images zijn byte voor byte gelijk gebleven. Blade-scripts kregen uitsluitend een beveiligingsnonce; de moderatiegeschiedenis gebruikt vooraf geladen gegevens met dezelfde presentatie.

## Beveiliging

- Content Security Policy met een nieuwe nonce per pagina, bescherming tegen inbedden/clickjacking, MIME-sniffing en onnodige browserrechten. HSTS alleen op HTTPS in productie. Inline styles blijven toegestaan voor de bestaande vormgeving; willekeurige inline scripts niet.
- HTML en ingelogde antwoorden mogen niet via gedeelde caches worden opgeslagen.
- Extra inloglimiet per gehasht e-mailadres, naast bestaande IP-limieten. Dit beperkt verspreide brute-forcepogingen zonder adressen in cachekeys te zetten.
- Na wachtwoordherstel worden bestaande databasesessies verwijderd en remember-tokens vernieuwd. Het extra wachtwoordveld bij het koppelen van sociale accounts wordt niet naar de sessie teruggeflasht.
- Gemini/NewsData HTTP-verzoeken volgen geen redirects met credentials. De bestaande afbeeldingscontrole, eigenaarcontroles, CSRF, AI-validatie en limieten voor bijdragen blijven behouden.
- Tests dwingen een in-memory database en testsleutel af. De aangeleverde testconfiguratie liet de databasekeuze nog open.
- De standaardseeder maakt geen account meer met een voorspelbaar testwachtwoord. Expliciete, lokaal beveiligde demo-opdrachten blijven beschikbaar.
- Overstap: gegevens lokaal kopiëren naar een nieuwe installatie, zonder oude broncode/vendor of geheime bestanden mee te leveren.

## Prestaties en betrouwbaarheid

- Samengestelde database-indexen voor datum-, lees- en likesortering, categorieën, reactielijsten, moderatiehistorie en privacyverzoeken. Geen niet-onderbouwde procentuele snelheidsclaim; het voordeel groeit met de hoeveelheid gegevens.
- De verhalenbalie laadt de laatste tien beoordelingen per verhaal in een gezamenlijke relationele query, in plaats van een extra query per getoonde rij.
- Queue retry_after standaard 2100 seconden, langer dan de worker-timeout van 1800 seconden.
- Scheduler overlaplock van 30 naar 180 minuten voor de bestaande drie-uurlijkse import, zodat langzame illustratiebatches niet zo snel dubbel starten.
- Cloudflare fout 4006 met expliciete uitgeputte dagquota wordt niet driemaal nutteloos herhaald. Tijdelijke rate-/servicefouten houden hun bestaande beperkte retrylogica.
- Composer lock bevat nu ook Socialite, Apple- en Microsoftproviders. Geen grote Laravel-versiesprong. Composer valideert het pakket; de audit meldt geen bekende kwetsbaarheidsadviezen op het controlemoment.

## Opgeruimd

- Oude overlay-installers, losse oude leesmijbestanden, bestandslijsten en het onbedoelde bestand `count())` zitten niet in dit project.
- Ongebruikte Laravel-welkomstpagina en ongebruikte Vite/npm-starterbestanden zijn verwijderd. Geen npm-build nodig.
- Verlaten Pollinations/hybride generatiecode en bijbehorende specifieke tests/configuratie zijn verwijderd. Bestaande afbeeldingen uit die periode blijven geldig; ze worden niet gewist of opnieuw gegenereerd. Cloudflare en ComfyUI blijven beschikbaar.
- Voorbeeldtests zonder toepassing op Lichtpuntjes verwijderd. Tests voor alle actuele functies behouden.
- Gegenereerde caches, logs, vendor, oude backups, echte database en .env worden niet verspreid. De overstap neemt actieve batchstatus, quota en geldige lokale afbeeldingen lokaal over.
- Bestaande migraties en compatibele news:photos-opdracht zijn behouden: die zijn nog nodig voor bestaande installaties en scripts.

## Getest

- 100 PHP-tests, 726 assertions: nieuwsimport, sortering/paginering, accounts, sociaal inloggen met mocks, badges, reacties/antwoorden, AI-moderatie, eigen verhalen, illustraties, batchvervanging, privacy, cookies en nieuwe beveiligingscontroles.
- JavaScript-regressiecontroles voor de bestaande artikelpopup, deelopties, likes, reacties, bronlinks en NL/EN.
- Composer-validatie en kwetsbaarheidsaudit; routecache kan worden opgebouwd.
- Overstaphelper getest met een afzonderlijke proefdatabase: consistente kopie, ongewijzigd origineel en behoud van gegevens.
- CSS/ontwerpassets vergeleken met de upload: identiek.

Niet live getest: jouw Windows/Herd-installatie, jouw echte accounts bij Google/Apple/Microsoft, mailbezorging of AI-providers met jouw sleutels. Geen verzoeken met je echte API-sleutels uitgevoerd. Geen nieuwe visuele browsertest op jouw apparaat; de vormgevingsbestanden zijn ongewijzigd.

De privacyverklaring is nog concept wegens ontbrekende definitieve bewaartermijnen/leveranciersafspraken. De bestaande handmatige afhandeling van privacyverzoeken is niet veranderd.
