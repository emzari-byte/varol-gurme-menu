# Varol Gurme QR Menu - Project Notes

Last updated: 2026-04-30

## Project

Varol Gurme is an OpenCart 3.0.3.8 based QR menu and restaurant management system.

Main local path:

```text
C:\xampp\htdocs\menu
```

Live path:

```text
/home/varolver/public_html/menu
```

GitHub repository:

```text
https://github.com/emzari-byte/varol-gurme-menu
```

Live site root:

```text
https://varolveranda.com/menu
```

Akınsoft Web Entegrasyon base URL:

```text
https://varolveranda.com
```

## Current Architecture

The system is now restaurant-focused rather than default OpenCart-focused.

Main active modules:

- Customer QR menu
- Waiter panel
- Kitchen panel
- Restaurant settings
- Table management
- Waiter management
- Allergen definitions
- Menu recommendation engine
- Production test panel
- Akınsoft ERP integration

The admin menu has been simplified around restaurant operations.

## QR Table Session Security

Implemented table session token protection:

- Customer scans QR.
- Table session token is created.
- Customer can order only while session token matches the active table token.
- When payment is taken, active table session token is cleared.
- Old customer phone cannot order remotely after leaving.
- New customer must scan QR again.

Important DB column:

```sql
active_session_token
```

in:

```text
menu_restaurant_table_status
```

## Menu Flow

Main customer pages:

- `catalog/controller/common/home.php`
- `catalog/controller/common/menu.php`
- `catalog/controller/common/category.php`
- `catalog/controller/common/menu_recommendation.php`

Product card styling was unified between menu and category pages.

Home page product sections were moved away from default OpenCart modules and into restaurant-owned management.

Relevant custom table:

```text
menu_restaurant_home_section
```

If this MySQL table is marked as crashed, repair it from phpMyAdmin.

## Waiter Panel

Waiter panel was redesigned for restaurant use.

Current behavior:

- Desktop: tables shown in a multi-column layout.
- Mobile: tables shown as two per row where possible.
- Waiter users see only assigned tables.
- Admin sees all tables.
- Waiter break flow exists.
- If waiter goes on break, assigned table notifications can be delegated to another active waiter.
- Waiter call button reset duration is configurable from restaurant settings.

Known important bug fix:

- A completed or already progressed order must not be actionable again by restricted waiter users.
- Waiter panel status/action buttons were simplified into a single main action area.

## Kitchen Panel

Kitchen panel V2 exists.

Implemented ideas:

- New order instant refresh / notification flow.
- Sound alert.
- Preparation time countdown from product tag values.
- Late order visual warning.

Product preparation time comes from OpenCart product tags, e.g.

```text
15 - 20 Dk
```

## Restaurant Settings

Settings are stored in a custom table:

```text
menu_ayarlar
```

Important settings areas:

- Menu settings
- AI and communication
- ERP
- Waiter call reset duration
- Bill request reset duration
- WhatsApp phone
- Feedback email
- OpenAI key
- Weather key
- Logo settings

Do not keep secrets in Git.

## Akınsoft ERP Integration

There are multiple integration modes:

- `local_firebird`
- `bridge_agent`
- `web_ent`

Current active direction:

```text
Akınsoft Web Entegrasyon
```

Reason:

- Live server should not directly connect to local Firebird.
- Firebird port should not be exposed publicly unless absolutely required.
- Akınsoft Web Entegrasyon can call the website endpoints and pull orders.

Important recent fix:

If `pdo_firebird` is not installed, waiter panel must not fail when sending an order to kitchen. It now falls back to WebEnt queue.

Recent commit:

```text
58ecea9 Fallback Akinsoft waiter export to WebEnt queue
```

## Akınsoft WebEnt Endpoints

Root API folder deployed outside `/menu`:

```text
/home/varolver/public_html/Api/WebEnt9
```

Akınsoft Web Entegrasyon program should use site address:

```text
https://varolveranda.com
```

Known calls observed from Akınsoft:

```text
POST /Api/WebEnt9/Test/
GET  /Api/WebEnt9/SiparisSayiGetirV2
```

Request log file:

```text
/home/varolver/public_html/menu/akinsoft_webent_request.log
```

Do not commit this log.

Current status:

- Test endpoint is reached.
- SiparisSayiGetirV2 endpoint is reached.
- Waiter panel can send order without pdo_firebird error.
- Next task is to implement the order detail endpoint that Akınsoft calls after detecting pending orders.

When Akınsoft logs a new endpoint after `SiparisSayiGetirV2`, inspect that endpoint and implement the expected response format.

## cPanel Deploy

Deployment uses GitHub + cPanel Git Version Control.

Workflow:

1. Commit and push to GitHub `main`.
2. cPanel Git Version Control.
3. Select `varol-gurme-menu`.
4. `Update from Remote`.
5. `Deploy HEAD Commit`.

Important deployment file:

```text
.cpanel.yml
```

It deploys:

- OpenCart app files into `/public_html/menu`
- WebEnt API into `/public_html/Api/WebEnt9`

## Known Caution

There is an unowned local git status item:

```text
D bridge/akinsoft_bridge_config.example.php
```

Do not revert or stage it unless the user explicitly asks.

## Next Tasks

1. Complete Akınsoft WebEnt order detail endpoint.
2. Return order products with Turkish product names, quantities, prices, table number and note.
3. Mark order as exported only after Akınsoft successfully receives it.
4. Later, sync paid/closed status from Akınsoft back to restaurant table/session state.
5. Move remaining footer feedback email and logo settings if not fully completed.
6. Continue polishing customer menu UX after ERP flow is stable.

## New Chat Startup Prompt

Use this in a new conversation:

```text
Varol Gurme QR Menü projesine devam ediyoruz. Repo: https://github.com/emzari-byte/varol-gurme-menu. Local yol: C:\xampp\htdocs\menu. Canlı yol: /home/varolver/public_html/menu. PROJECT_NOTES.md dosyasını oku ve kaldığımız yerden devam et. Şu an Akınsoft Web Entegrasyon akışındayız: /Api/WebEnt9/Test ve /Api/WebEnt9/SiparisSayiGetirV2 çağrılıyor. Sıradaki iş Akınsoft'un sipariş detay endpointini yakalayıp ürün/adet/fiyat/masa bilgilerini doğru formatta döndürmek.
```
