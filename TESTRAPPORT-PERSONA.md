# Testrapport — Lichtpunt vanuit 20 persona’s

**Datum:** 13 oktober 2026  
**Omgeving:** lokale Herd-site (`lichtpunt.test`)  
**Methode:** handmatige, heuristische rondgang door de website; brede startweergave en een smalle mobiele weergave. De persona’s zijn simulaties, geen 20 echte proefpersonen. Leeftijd is niet gebruikt als aanname over digitale vaardigheid: de vier gebruiksbehoeften zijn per leeftijdsgroep opnieuw bekeken.

## Samenvatting

De belangrijkste routes zijn te vinden en de belangrijkste interface-elementen werken in de bekeken flows. De categorieknoppen filteren artikelen, een artikel opent in een leesvenster, de aanmeld- en inlogformulieren zijn gelabeld en een bezoeker zonder account wordt naar inloggen geleid wanneer die een lichtpuntje wil schrijven.

Er zijn drie bruikbare verbeterpunten gevonden:

1. De taalwissel vanaf de homepage vertaalt niet alle onderdelen direct mee.
2. Een lege resultatenlijst verwijst naar zoeken, terwijl er geen zoekveld zichtbaar is.
3. Bij een eerste bezoek concurreren een grote startpagina-prompt en de cookiekeuze om aandacht voordat de bezoeker rustig de inhoud kan verkennen.

De lokale testomgeving bevat vier expliciet als fictief aangeduide demo-artikelen. Daardoor konden echte nieuwsbronnen, echte afbeeldingsdekking en de productie-inhoud niet worden beoordeeld.

## De 20 persona’s

Dezelfde vier gebruiksstijlen zijn voor de leeftijden 20, 30, 40, 50 en 60 doorlopen. De context varieert; de vaardigheid wordt niet uit de leeftijd afgeleid.

| Leeftijd | Persona | Doel tijdens de rondgang | Bevinding |
|---:|---|---|---|
| 20 | Snelle mobiele lezer | In een korte pauze een interessant verhaal vinden en openen. | Artikelen zijn op een smal scherm bereikbaar; de eerste startpagina-prompt vraagt aandacht voordat de inhoud kan worden verkend (bevinding 3). |
| 20 | Doelgerichte zoeker | Een onderwerp zoals media snel terugvinden. | Categorieën filteren; bij nul resultaten noemt de melding een zoekterm, maar er is geen zoekveld (bevinding 2). |
| 20 | Betrokken bijdrager | Een verhaal lezen en daarna reageren of zelf iets schrijven. | Artikelvenster en inlogroute werken; schrijven leidt voor een uitgelogde bezoeker naar inloggen. De volledige account-/verificatieflow is niet doorlopen. |
| 20 | Voorzichtige nieuwkomer | Eerst begrijpen wat cookies en privacy betekenen. | Cookiekeuze en privacylink zijn aanwezig; de startprompt maakt de eerste keuze drukker (bevinding 3). |
| 30 | Snelle mobiele lezer | Tussen andere bezigheden snel positief nieuws bekijken. | De mobiele presentatie blijft bruikbaar; de startprompt neemt bij het eerste bezoek de voorrang (bevinding 3). |
| 30 | Doelgerichte zoeker | Gericht een categorie openen en begrijpen wat er ontbreekt. | De categorie werkt; de lege-staattekst suggereert een niet-beschikbare zoekfunctie (bevinding 2). |
| 30 | Betrokken bijdrager | Een reactie of eigen lichtpuntje delen. | Registratie legt naam, e-mailprivacy, wachtwoordeisen en huisregels uit; accountaanmaak en posten zijn niet uitgevoerd. |
| 30 | Voorzichtige nieuwkomer | Taal, cookies en privacy controleren vóór deelname. | Cookiekeuze is beschikbaar; bij direct kiezen van Engels is de interface vertaald, maar de taalwissel op de homepage vertaalt niet alles direct (bevinding 1). |
| 40 | Snelle mobiele lezer | Tijdens een korte onderbreking een verhaal lezen zonder veel stappen. | De artikelkaart opent in een leesvenster en toont de samenvatting; de eerste startprompt onderbreekt de verkenning (bevinding 3). |
| 40 | Doelgerichte zoeker | Een bepaald onderwerp vinden zonder alle berichten te doorlopen. | Filteren werkt, maar er is geen zoekveld ondanks de verwijzing naar een zoekterm (bevinding 2). |
| 40 | Betrokken bijdrager | Een verhaal lezen en de communityfuncties vinden. | Hartjes en reacties zijn zichtbaar; interactie vraagt om inloggen. De vervolgstap na een echte login is niet geverifieerd. |
| 40 | Voorzichtige nieuwkomer | Bronvermelding, privacy en communityregels controleren. | Privacy en huisregels zijn via de navigatie bereikbaar; in de lokale demo verwijzen artikelbronnen naar `example.test`. |
| 50 | Rustige lezer | Een artikel openen, de samenvatting lezen en verder klikken. | Leesvenster toont samenvatting, bron en reactiegebied; demo-inhoud begrenst de beoordeling van echte bronnen. |
| 50 | Doelgerichte zoeker | Een onderwerp of categorie selecteren en relevante berichten vinden. | De categorieknoppen werken; lege categorieën tonen de misleidende verwijzing naar zoeken (bevinding 2). |
| 50 | Betrokken bijdrager | Begrijpen hoe een eigen bijdrage kan worden geplaatst. | De route naar schrijven is vindbaar; zonder account volgt de inlogpagina. Registreren en verificatie zijn niet echt voltooid. |
| 50 | Voorzichtige nieuwkomer | Begrijpen wat de site met gegevens en cookies doet. | Privacyverklaring en cookiekeuze zijn bereikbaar; een gelijktijdige promotionele prompt vraagt extra aandacht (bevinding 3). |
| 60 | Rustige, leesgerichte bezoeker | Op een klein scherm navigeren en tekst op een rustig tempo lezen. | De bekeken smalle weergave had geen horizontale overflow; de navigatie loopt over meerdere regels. Er is geen formele contrast- of schermlezer-audit uitgevoerd. |
| 60 | Stapsgewijze zoeker | Met een duidelijke categorie een passend artikel vinden. | Filters zijn zichtbaar en bedienbaar; de lege-staattekst biedt een zoekstap die niet beschikbaar is (bevinding 2). |
| 60 | Nieuwe bijdrager | Nagaan wat nodig is om mee te doen en een verhaal te schrijven. | Formuliervelden hebben labels en wachtwoordeisen staan erbij; een echte registratie is niet uitgevoerd. |
| 60 | Privacy- en toegankelijkheidsbewuste bezoeker | Eerst privacy, cookies en leesbaarheid controleren. | Cookiekeuze en privacy-informatie zijn vindbaar; de eerste bezoekservaring is druk door de startprompt naast de cookiekeuze (bevinding 3). |

## Bevindingen

### 1. De taalwissel vertaalt de homepage niet volledig direct mee

**Prioriteit: P2 — merkbare gebruiksfrictie**

Wanneer op de Nederlandse homepage met de NL/EN-knop naar Engels wordt gewisseld, blijven onderdelen van de bestaande pagina Nederlands, waaronder de titels/metadata van de demo-artikelen en de titel van het deelvenster. Na een volledige navigatie naar een Engelse URL zijn meer onderdelen wel vertaald. De bezoeker moet dus zelf kunnen bedenken dat herladen of opnieuw navigeren nodig is.

**Aanbeveling:** laat de taalwissel alle zichtbare en verborgen UI-teksten, feedinhoud en metadata onmiddellijk verversen, of maak duidelijk dat een herlaadactie nodig is. Als artikelinhoud bewust in de oorspronkelijke taal blijft, geef die taal dan expliciet aan.

### 2. De lege resultatenmelding belooft zoeken zonder zoekveld

**Prioriteit: P2 — onduidelijke vervolgstap**

Na het kiezen van **Media** waren er in deze demo geen resultaten. De melding luidde: “Geen berichten gevonden. Probeer een andere zoekterm of categorie.” De zichtbare bediening bevat categorie- en sorteerknoppen, maar geen zoekveld. “Andere zoekterm” stuurt de bezoeker dus naar een actie die op die pagina niet mogelijk is.

**Aanbeveling:** voeg een zichtbaar zoekveld toe, of verwijder de verwijzing naar een zoekterm en geef alleen beschikbare vervolgstappen.

### 3. Eerste bezoek krijgt twee concurrerende prompts

**Prioriteit: P2 — drempel voordat de inhoud wordt bereikt**

Bij de eerste opening verscheen een groot venster om Lichtpunt als startpagina in te stellen; tegelijk was ook de cookiekeuze zichtbaar. De startpagina-prompt bedekte een groot deel van de pagina. De optie om door te gaan naar de lichtpuntjes is aanwezig, maar de combinatie vraagt een nieuwe bezoeker eerst meerdere beslissingen te nemen voordat die rustig de website kan verkennen.

**Aanbeveling:** stel de startpagina-prompt uit tot na de eerste leesactie of toon die minder prominent. Houd de cookiekeuze apart en duidelijk, zodat de bezoeker niet twee overlays tegelijk hoeft te verwerken.

## Positieve observaties

- De homepage toont duidelijke categorie- en sorteerbediening; de categorie-filter werkte in de test.
- Een artikel opent in een leesvenster met een sluitknop, bronvermelding, samenvatting en reactiesectie.
- De registratievelden hebben labels. De uitleg vermeldt dat de e-mail privé blijft, noemt de wachtwoordeisen en linkt naar de huisregels.
- Een niet-ingelogde bezoeker wordt bij het schrijven van een lichtpuntje naar inloggen geleid.
- Cookie-instellingen bieden afzonderlijke keuzes voor noodzakelijke cookies en leesstatistieken.
- De privacy- en huisregelpagina’s zijn via de voettekst bereikbaar.
- In de bekeken smalle mobiele weergave was geen horizontaal scrollen nodig.

## Afbakening en beperkingen

- Dit is een heuristische simulatie van 20 persona’s, geen onderzoek met echte deelnemers. De uitkomsten zeggen niet hoe alle mensen van een bepaalde leeftijd zich gedragen.
- De lokale site toonde vier fictieve demo-artikelen, met ontbrekende foto’s en bronadressen op `example.test`. De productie-feed en de geldigheid van echte nieuwsbronnen zijn niet beoordeeld.
- Er zijn geen echte accounts aangemaakt; e-mailverificatie, wachtwoordherstel, reacties plaatsen, moderatie en beheer zijn daarom niet end-to-end getest.
- Er is geen formele audit uitgevoerd met schermlezer, toetsenbord-only, kleurcontrastmeter of verschillende browsers/apparaten.
- Het openen van één demo-artikel registreerde lokaal één lezing. Er is geen account- of inhoudsdata aangemaakt.

## Conclusie

De basis voor lezen, filteren, accountinformatie en cookie-/privacykeuzes is aanwezig. De hoogste waarde zit nu in het consistent maken van de taalwissel en in het verwijderen van de niet-beschikbare zoekinstructie. Daarna kan de eerste-bezoeksprompt rustiger worden aangeboden. Een vervolgronde met echte gebruikers en echte productieartikelen is nodig om conclusies over begrijpelijkheid, bronvertrouwen en toegankelijkheid te bevestigen.
