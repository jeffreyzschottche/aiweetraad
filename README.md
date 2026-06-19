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

## Deploy met Coolify

Maak in Coolify twee losse applicaties vanuit dezelfde repository.

### Frontend

- Build pack: `Dockerfile`
- Base directory / build directory: `frontend`
- Dockerfile: `Dockerfile`
- Port: `3000`
- Belangrijkste env:
  ```
  NUXT_PUBLIC_API_BASE_URL=https://jouw-api-domein.nl/api/v1
  NUXT_PUBLIC_ADS_ENABLED=false
  ```

### Backend

- Build pack: `Dockerfile`
- Base directory / build directory: `backend`
- Dockerfile: `Dockerfile`
- Port: `8000`
- Belangrijkste env:
  ```
  APP_ENV=production
  APP_DEBUG=false
  APP_URL=https://jouw-api-domein.nl
  FRONTEND_URL=https://jouw-frontend-domein.nl
  SANCTUM_STATEFUL_DOMAINS=jouw-frontend-domein.nl
  APP_KEY=base64:...
  ```

Gebruik voor productie bij voorkeur een externe database via Coolify env-vars. Als je SQLite gebruikt,
zet dan een persistent volume op `/var/www/html/database` en voer na de eerste deploy uit:

```bash
php artisan migrate --force
php artisan content:refresh-demo
```

## Hoe de AI-antwoorden werken

Elke vraag krijgt één antwoord per ingeschakeld AI-model (zie `ai_models`-tabel: ChatGPT, Claude,
Gemini, Grok en DeepSeek). Antwoorden worden in de `answers`-tabel opgeslagen en daarmee **gecached**: een
vraag wordt maar één keer gegenereerd.

- **Seeden / standaard**: `AI_GENERATION_ENABLED=false` → de `StubDriver` levert gratis, offline,
  realistische voorbeeldantwoorden. Ideaal om de site te vullen zonder kosten of API-keys.
- **Echt aanzetten**: zet in `backend/.env`:
  ```
  AI_GENERATION_ENABLED=true
  AI_ALLOW_STUB_FALLBACK=false

  OPENAI_API_KEY=...
  OPENAI_ADMIN_KEY=... # optioneel, alleen voor OpenAI admin-kostenrapportage
  GEMINI_API_KEY=...
  ANTHROPIC_API_KEY=...
  DEEPSEEK_API_KEY=...
  XAI_API_KEY=...

  OPENAI_DEFAULT_MODEL=gpt-5.4-mini
  OPENAI_FALLBACK_MODEL=gpt-5.4
  GEMINI_DEFAULT_MODEL=gemini-2.5-flash
  GEMINI_FALLBACK_MODEL=gemini-2.5-flash-lite
  ANTHROPIC_DEFAULT_MODEL=claude-sonnet-4-6
  ANTHROPIC_FALLBACK_MODEL=claude-haiku-4-5
  DEEPSEEK_DEFAULT_MODEL=deepseek-chat
  DEEPSEEK_FALLBACK_MODEL=deepseek-reasoner
  XAI_DEFAULT_MODEL=grok-4.3

  AI_BUDGET_ALERTS_ENABLED=true
  AI_ADMIN_EMAIL=admin@jouwdomein.nl
  AI_BUDGET_ALERT_THROTTLE_MINUTES=60
  ```
  De provider-router kiest dan per antwoord een provider/model op basis van beschikbare keys en
  providerstatus. DeepSeek-saldo wordt live opgehaald; OpenAI admin-kosten kunnen worden opgehaald
  met `OPENAI_ADMIN_KEY`, maar gewone OpenAI project API keys geven geen live saldo terug. De app
  houdt daarnaast lokale geschatte spend bij in `ai_provider_usages`. Als geen provider beschikbaar
  is, wordt het antwoord `failed` in plaats van stilletjes een nepantwoord te tonen. Offline fallback kan alleen bewust met
  `AI_ALLOW_STUB_FALLBACK=true`. Als `AI_ADMIN_EMAIL` is gevuld, stuurt de backend maximaal één
  alert per throttle-window wanneer er geen bruikbare provider over is of wanneer alle providers falen.

Wanneer een bezoeker een **nieuwe** vraag stelt via `/vraag-stellen`, genereert de AI direct en
wordt het resultaat gecached.

### Demo-content opnieuw vullen

Gebruik dit commando om categorieën, voorbeeldvragen en gevarieerde demo-antwoorden opnieuw te vullen
zonder externe AI-kosten:

```bash
php artisan content:refresh-demo
```

### Echte AI-antwoorden voor bestaande vragen genereren

Gebruik dit om bestaande demo-antwoorden door echte provider-antwoorden te vervangen. Begin altijd
met een dry-run:

```bash
php artisan content:generate-ai-answers --dry-run --force
php artisan content:generate-ai-answers --force
```

Handig voor één vraag:

```bash
php artisan content:generate-ai-answers --dry-run --force --question=hoe-val-ik-makkelijker-in-slaap
php artisan content:generate-ai-answers --force --question=hoe-val-ik-makkelijker-in-slaap
```

Kosten/failures bekijken:

```bash
php artisan ai:usage
```

### Oma Weet Raad onderwerpen importeren

Gebruik eerst altijd een dry-run. Het command importeert onderwerpen/links als eigen vragen en bewaart
de bron-hash, zodat dezelfde bron niet dubbel binnenkomt.

```bash
php artisan content:import-oma --dry-run --limit=100 --pages=10
php artisan content:import-oma --limit=100 --pages=10
```

Wil je direct AI-antwoorden laten genereren voor de geimporteerde vragen:

```bash
php artisan content:import-oma --limit=100 --pages=10 --generate-ai
```

Voor bulk-imports kun je OpenAI Batch gebruiken. Dat is goedkoper, maar async: je start de batch,
wacht tot OpenAI klaar is, en haalt daarna de antwoorden op.

```bash
php artisan content:generate-ai-answers-batch --source-name=omaweetraad.nl --limit=100
php artisan content:ai-batch-status batch_...
php artisan content:collect-ai-batch batch_...
```

Live vragen via `/vraag-stellen` blijven de normale directe generator gebruiken.

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
