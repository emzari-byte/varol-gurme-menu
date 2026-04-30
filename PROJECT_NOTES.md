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

There are two remaining integration modes:

- `bridge_agent`
- `local_firebird`

Current active direction:

```text
Bridge Agent
```

Reason:

- Firebird port should not be exposed publicly.
- The live server does not need `pdo_firebird`.
- A single PHP bridge script runs on the restaurant computer, reads local Firebird, pulls pending orders from the live site, writes Akinsoft receipts, and reports status back.
- WebEnt was removed because it did not reliably expose the needed order-detail flow and limited field control.

Bridge files:

```text
bridge/akinsoft_bridge_agent.php
bridge/akinsoft_bridge_config.php
```

Bridge live endpoints:

```text
index.php?route=extension/module/akinsoft_bridge/pending
index.php?route=extension/module/akinsoft_bridge/mark
index.php?route=extension/module/akinsoft_bridge/sent
index.php?route=extension/module/akinsoft_bridge/paid
index.php?route=extension/module/akinsoft_bridge/syncTables
index.php?route=extension/module/akinsoft_bridge/syncPrices
```

Important:

- `restaurant_akinsoft_enabled` must be `1`.
- `restaurant_akinsoft_mode` should be `bridge_agent`.
- `restaurant_akinsoft_bridge_url` should be `https://varolveranda.com/menu/`.
- `restaurant_akinsoft_bridge_token` must match `bridge/akinsoft_bridge_config.php`.
- Waiter-created manual orders are queued with `integration_status = 'pending_export'` when they go to kitchen and Akinsoft integration is enabled.
- Table and price sync in bridge mode is triggered from the Akinsoft PC with `sync_bridge_tables.bat`, `sync_bridge_prices.bat`, or `sync_bridge_all.bat`.

## Removed WebEnt Flow

WebEnt API files and restaurant settings option have been removed. Do not deploy or depend on:

```text
/home/varolver/public_html/Api/WebEnt9
```

If the old live folder remains on hosting, it can be manually deleted after cPanel deploy.

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

## Known Caution

There is an unowned local git status item:

```text
D bridge/akinsoft_bridge_config.example.php
```

Do not revert or stage it unless the user explicitly asks.

## Next Tasks

1. Simplify Bridge Agent setup so it can run with one PHP command or a tiny Windows launcher.
2. Test bridge pending-order pull against the live site.
3. Test local Firebird receipt write from the bridge agent.
4. Later, sync paid/closed status from Akinsoft back to restaurant table/session state.
5. Move remaining footer feedback email and logo settings if not fully completed.
6. Continue polishing customer menu UX after ERP flow is stable.

## New Chat Startup Prompt

Use this in a new conversation:

```text
Varol Gurme QR Menü projesine devam ediyoruz. Repo: https://github.com/emzari-byte/varol-gurme-menu. Local yol: C:\xampp\htdocs\menu. Canlı yol: /home/varolver/public_html/menu. PROJECT_NOTES.md dosyasını oku ve kaldığımız yerden devam et. Akınsoft WebEnt akışı kaldırıldı; Bridge Agent yoluna geçiyoruz. Sıradaki iş bridge agent kurulumunu sadeleştirmek, canlı siteden pending sipariş çekmesini ve restoran bilgisayarındaki Firebird'e adisyon yazmasını test etmek.
```
