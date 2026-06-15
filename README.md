# AI Weet Raad

Stel je dagelijkse vraag en krijg meteen antwoord van **meerdere AI's tegelijk**. Vergelijk de
adviezen naast elkaar en stem met like/dislike op het antwoord dat jou het beste helpt.

Geïnspireerd op de opzet van "oma weet raad", maar met AI-gegenereerde antwoorden in plaats van
huismiddeltjes. Alle openbare pagina's (home, categorieën, vraagdetail, vraag stellen, zoeken,
contact, over ons, adverteren, privacy, voorwaarden) zijn nagebouwd; de inhoud is volledig eigen.

## Stack

| Laag      | Technologie                                   |
| --------- | --------------------------------------------- |
| Backend   | Laravel 12 + Sanctum (REST API onder `/api/v1`) |
| Frontend  | Nuxt 4 + Pinia + Tailwind CSS + GSAP          |
| Database  | SQLite (standaard, eenvoudig te wisselen)     |

```
aiweetraad/
├── backend/   # Laravel API
└── frontend/  # Nuxt 4 app
```

## Snel starten

### Backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate:fresh --seed
php artisan serve            # http://localhost:8000
```

### Frontend

```bash
cd frontend
npm install
cp .env.example .env
npm run dev                  # http://localhost:3000
```

Testaccount na seeden: `test@example.com` (wachtwoord via wachtwoord-reset of pas de seeder aan).

## Hoe de AI-antwoorden werken

Elke vraag krijgt één antwoord per ingeschakeld AI-model (zie `ai_models`-tabel: ChatGPT, Claude,
Gemini, Grok en DeepSeek). Antwoorden worden in de `answers`-tabel opgeslagen en daarmee **gecached**: een
vraag wordt maar één keer gegenereerd.

- **Seeden / standaard**: `AI_GENERATION_ENABLED=false` → de `StubDriver` levert gratis, offline,
  realistische voorbeeldantwoorden. Ideaal om de site te vullen zonder kosten of API-keys.
- **Echt aanzetten**: zet in `backend/.env`:
  ```
  AI_GENERATION_ENABLED=true
  ANTHROPIC_API_KEY=sk-ant-...
  ```
  Modellen met provider `claude` gebruiken dan de echte Anthropic API (`ClaudeDriver`). Mislukt een
  call of ontbreekt de key, dan valt hij netjes terug op de stub. Andere providers (openai/gemini)
  gebruiken voorlopig ook de stub — voeg een driver toe in `app/Services/AI/`.

Wanneer een bezoeker een **nieuwe** vraag stelt via `/vraag-stellen`, genereert de AI direct en
wordt het resultaat gecached.

### Demo-content opnieuw vullen

Gebruik dit commando om categorieën, voorbeeldvragen en gevarieerde demo-antwoorden opnieuw te vullen
zonder externe AI-kosten:

```bash
php artisan content:refresh-demo
```

### Antwoorden (her)genereren vanuit tinker

```php
$q = App\Models\Question::first();
app(App\Services\AI\AnswerGenerator::class)->generateForQuestion($q, force: true);
```

## Stemmen (like / dislike)

Bezoekers stemmen anoniem (een willekeurige sleutel in `localStorage`) of als ingelogde gebruiker.
Stemmen zijn uniek per (antwoord, stemmer) en togglebaar. De like/dislike-tellers worden als delta
bijgewerkt, zodat geseede basistellingen behouden blijven. De leaderboard "AI's aan de top" rangschikt
modellen op de netto score van hun antwoorden.

## Advertentieplekken

De `<AdSlot>`-component bevat nette placeholders op drie posities. Vervang de binnenkant door je
echte ad-tag (bijv. Google AdSense / Ad Manager):

- `leaderboard` — banner onder de header (op elke pagina).
- `in-content` — native blok tussen de antwoorden op een vraagpagina.
- `sidebar` — meescrollende 300×600 in de zijbalk.

## Belangrijkste API-endpoints (`/api/v1`)

| Methode | Endpoint                     | Beschrijving                          |
| ------- | ---------------------------- | ------------------------------------- |
| GET     | `/home`                      | Homepage-data (categorieën, vragen, stats) |
| GET     | `/categories`                | Alle categorieën                      |
| GET     | `/categories/{slug}`         | Categorie + vragen (gepagineerd)      |
| GET     | `/questions`                 | Vragen (filter `q`, `category`, `sort`) |
| GET     | `/questions/{slug}`          | Vraag + AI-antwoorden + gerelateerd   |
| POST    | `/questions`                 | Nieuwe vraag (genereert AI-antwoorden) |
| POST    | `/answers/{id}/vote`         | Like/dislike (`value` 1 of -1)        |
| GET     | `/ai-models/leaderboard`     | AI's gerangschikt op score            |
| GET     | `/pages/{slug}`              | Statische paginacontent               |
| POST    | `/contact`                   | Contactformulier                      |

Auth-endpoints (register/login/logout/me/wachtwoord/verificatie) komen uit de Sanctum-starterkit.
