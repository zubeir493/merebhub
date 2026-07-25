# MerebHub

Curated Ethiopian software marketplace built with Laravel 13, Livewire 4, Filament 5, PostgreSQL, WooCommerce, Chapa, Keygen, Mailtrap, and S3-compatible storage.

## Setup

```bash
composer install
npm install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
```

Configure these groups in `.env`:

- PostgreSQL: `DB_*`
- Mailtrap: `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`
- Cloudflare R2: `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_BUCKET`, `AWS_ENDPOINT`, `AWS_URL`
- WooCommerce: `WC_API_URL`, `WC_CONSUMER_KEY`, `WC_CONSUMER_SECRET`, `WC_SITE_URL`, `WC_WEBHOOK_SECRET`
- Keygen: `KEYGEN_API_URL`, `KEYGEN_API_TOKEN`, `KEYGEN_ACCOUNT_ID`, `KEYGEN_POLICY_ID`

`WC_API_URL` should normally end in `/wp-json/wc/v3`.

## WooCommerce Webhook

Create a WooCommerce webhook for order updates:

- Delivery URL: `https://your-storefront.example/webhooks/woocommerce`
- Secret: the same value as `WC_WEBHOOK_SECRET`
- Topic: Order updated

Completed or processing orders are mirrored locally, then license creation and purchase email delivery are queued.

## Runtime

Run the web app, queue worker, and frontend watcher during development:

```bash
composer run dev
```

In production, keep a queue worker running and execute Laravel's scheduler every minute. The hourly catalog pull is registered automatically.

## Admin

Filament is available at `/admin`. The demo seeder creates:

- Email: `admin@merebhub.test`
- Password: `password`

Override these before seeding with `DEMO_ADMIN_EMAIL` and `DEMO_ADMIN_PASSWORD`.

The dashboard reports whether WooCommerce, Keygen, and transactional mail are configured.

## Tests

```bash
php artisan test
```
