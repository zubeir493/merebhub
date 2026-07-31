# Implementation Plan: Sage 11 WooCommerce Theme (MerebHub Storefront Port)

## Overview

Incrementally build the Sage 11 WordPress theme that ports the MerebHub Laravel storefront into a WooCommerce-backed Blade/Alpine.js frontend. Tasks are ordered so each step produces working, integrated code before moving to the next layer: scaffold → design system → layout → components → page-by-page templates → WooCommerce integration → auth/account pages → testing.

## Tasks

- [x] 1. Scaffold theme structure and build pipeline
  - Create all directories per the design's directory structure (`app/View/Composers/`, `app/Providers/`, `config/`, `resources/css/`, `resources/js/`, `resources/views/`, `resources/heroicons/`)
  - Write `composer.json` with `roots/acorn ^4.0`, `roots/sage-dirs ^1.0`, `composer/installers ^2.2`, and dev deps `wp-phpunit/wp-phpunit ^6.5`, `lucatume/wp-browser ^4.3`, `giorgiosironi/eris ^0.11`
  - Write `package.json` with `alpinejs ^3.14`, `@alpinejs/focus ^3.14`, `@roots/sage ^6.0`, `vite ^5.4`, `tailwindcss ^4.0`, `@tailwindcss/vite ^4.0`
  - Write `vite.config.js` using `@roots/sage` plugin + `@tailwindcss/vite` plugin, inputs `resources/css/app.css` and `resources/js/app.js`
  - Write `functions.php` bootstrapping Acorn, declaring WooCommerce theme support, and enqueueing Vite assets
  - Write blank `index.php` (Sage convention)
  - Write `config/app.php` registering the three service providers
  - _Requirements: 1.12, 20.1–20.7_

- [x] 2. Implement the Tailwind CSS v4 design system and Alpine.js entry point
  - [x] 2.1 Write `resources/css/app.css` with `@import 'tailwindcss'`, `@source` directives for Blade/JS/PHP, `@theme` block (`--font-sans`, `--color-brand`), `@layer base` (`scroll-behavior`, `[x-cloak]`, `::selection`), and `@layer components` (`.btn-primary`, `.btn-dark`, `.form-input`, `.form-label`, `.form-error`)
    - _Requirements: 1.1–1.12_
  - [x] 2.2 Write `resources/views/layouts/app.blade.php` master layout: `<html>`, `<head>` with Bunny Fonts `<link>`, `@vite` directive, `<body>` with header partial, `@yield('content')`, footer partial, toast container
    - _Requirements: 1.1, 1.12, 2.1, 3.1_
  - [x] 2.3 Write `resources/js/app.js` importing Alpine, registering `@alpinejs/focus`, exposing `window.Alpine`, defining `Alpine.data('clipboardCopy', ...)`, and calling `Alpine.start()`
    - _Requirements: 1.11, 13.3_

- [x] 3. Implement service providers and heroicons
  - [x] 3.1 Write `app/Providers/ThemeServiceProvider.php`: `boot()` declares WooCommerce theme support with image size config and registers all View Composers via `$this->app['view']->composer()`
    - _Requirements: 20.5, 20.6_
  - [x] 3.2 Write `app/Providers/HeroiconServiceProvider.php`: scans `resources/heroicons/outline/` and `resources/heroicons/solid/` and registers each SVG as an anonymous Blade component (`x-heroicon-o-*` / `x-heroicon-s-*`) with attribute injection
    - _Requirements: 20.3_
  - [x] 3.3 Write `app/Providers/WishlistServiceProvider.php`: registers `mh_wishlist_item` CPT with `public: false`, `show_in_rest: true`, and registers the REST endpoint `GET /mh/v1/wishlist` + `POST /mh/v1/wishlist/toggle`
    - _Requirements: 6.6, 20.4_

- [x] 4. Checkpoint — Verify scaffold, design system, and providers compile
  - Ensure all tests pass, ask the user if questions arise.

- [x] 5. Implement global layout partials
  - [x] 5.1 Write `resources/views/partials/header.blade.php`: sticky header with logo, desktop search form + Ctrl+K shortcut badge, wishlist icon (filled/outlined based on `$headerWishlistCount`), cart icon with badge, account dropdown (logged-in: name/email/links/logout; guest: login link), mobile hamburger + slide-down nav panel — all driven by Alpine.js `x-data="{ mobileOpen: false, accountOpen: false }"`
    - _Requirements: 2.1–2.11_
  - [x] 5.2 Write `resources/views/partials/footer.blade.php`: dark footer with four-column CSS grid (Brand, Marketplace, Developers, Your account), constrained to `max-w-[1500px]`
    - _Requirements: 3.1–3.4_
  - [x] 5.3 Write `app/View/Composers/LayoutComposer.php`: queries WooCommerce cart count and `mh_wishlist_item` CPT count; binds to `'*'`
    - _Requirements: 2.5, 2.6_

- [x] 6. Implement reusable Blade components
  - [x] 6.1 Write `resources/views/components/product-card.blade.php`: `16/10` image container with group-hover scale, discount badge (`Save {n}%`), truncated product name link, author·category meta, ETB price, star rating row; accepts `$product` WC object prop
    - _Requirements: 19.1–19.7_
  - [x] 6.2 Write unit test for discount badge percentage computation (`compute_mh_discount_pct` helper)
    - Concrete examples: price=800, compare=1000 → 20%; price=999, compare=1000 → 0% (no badge); price=500, compare=1000 → 50%
    - _Requirements: 19.2_
  - [x] 6.3 Write property test for discount percentage calculation
    - **Property 1: Discount percentage calculation is always a whole number in [1, 99]**
    - **Validates: Requirements 19.2, 22.1**
  - [x] 6.4 Write `resources/views/components/toast.blade.php`: success (`role="status" aria-live="polite"`, teal icon) and error (`role="alert" aria-live="assertive"`, rose icon) variants; Alpine.js `x-init="setTimeout(...)"` auto-dismiss; `@keydown.escape.window` dismiss; enter/leave transitions; `x-cloak`; fixed container at `right-4 top-20 z-50`
    - _Requirements: 18.1–18.7_
  - [x] 6.5 Write example-based unit test for toast ARIA roles
    - Assert success toast renders `role="status" aria-live="polite"`, error toast renders `role="alert" aria-live="assertive"`
    - _Requirements: 18.1, 18.2, 22.7_

- [x] 7. Implement home page
  - [x] 7.1 Write `app/View/Composers/HomeComposer.php`: queries deals (`on_sale`), top products (`orderby: popularity`, limit 12), featured (`_featured=yes`, limit 4), full catalog (paginated, limit 24); injects `$categories`, `$deals`, `$topProducts`, `$featured`, `$products`
    - _Requirements: 4.1–4.7_
  - [x] 7.2 Write `resources/views/home.blade.php`: category strip (scrollable, icon+link per category), hero section (background image, gradient overlay, headline, CTA), deals bar (conditional, up to 5 items with rose price), "Top selling this week" ranked list (conditional, up to 12 with thumbnail/name/rating/category/price), "Made in Ethiopia" featured grid (conditional, up to 4 Product_Card), main catalog grid (`sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4`, Product_Card), empty-state placeholder when no products
    - _Requirements: 4.1–4.7_
  - [x] 7.3 Write property test for conditional section rendering
    - **Property 6: Deals bar rendered iff at least one product has `sale_price < regular_price`; top-sellers section rendered iff top-sellers collection is non-empty**
    - **Validates: Requirements 4.3, 4.4, 22.6**

- [x] 8. Implement shop / store page
  - [x] 8.1 Write `app/View/Composers/ShopComposer.php`: reads `$_GET` params (`q`, `category`, `platform`, `sort`), detects active collection tab, calls `buildWcArgs()` + `getDistinctCategories()` + `getDistinctPlatforms()`, injects paginated `$products` + all filter state variables
    - _Requirements: 5.1–5.9_
  - [x] 8.2 Write `resources/views/store/index.blade.php`: dark banner, collection tabs with active state and `aria-current="page"`, search+sort+filter form (GET), product count + "Clear filters" link, product grid (Product_Card), empty-state card, WooCommerce pagination; sort and filter popovers via Alpine `@click.away`; form controls pre-populated from `$_GET` state
    - _Requirements: 5.1–5.9_
  - [x] 8.3 Write stub views `store/new-arrivals.blade.php`, `store/bestsellers.blade.php`, `store/deals.blade.php` that each include `store/index.blade.php` with appropriate `$collection` preset
    - _Requirements: 5.2_
  - [x] 8.4 Write property test for filter URL round-trip
    - **Property 4: For any valid (`q`, `category`, `platform`, `sort`) GET params, rendered form controls must reflect those exact values**
    - **Validates: Requirements 5.9, 22.4**

- [x] 9. Implement single product page
  - [x] 9.1 Write `app/View/Composers/SingleProductComposer.php`: resolves WC product, fetches vendor user, related products (limit 4), purchasable variations/plans, wishlist state for current user
    - _Requirements: 6.1–6.10_
  - [x] 9.2 Write `resources/views/single-product.blade.php`: breadcrumb nav, `16/10` product image, `<h1>` name + tagline + author link + star rating, plan `<select>` + "Add to cart" `.btn-primary` (or amber unavailable box when no plans), wishlist toggle button (filled/outlined/link-for-guests), trust badge, "About this software" + "Why MerebHub?" sidebar, "More in {category}" related products grid (conditional)
    - _Requirements: 6.1–6.10_

- [x] 10. Implement search results page
  - [x] 10.1 Write `app/View/Composers/SearchComposer.php`: reads `get_search_query()`, queries products via `s` arg (paginated), queries vendor users matching the query by display name/meta; injects `$query`, `$products`, `$vendors`
    - _Requirements: 7.1–7.7_
  - [x] 10.2 Write `resources/views/search.blade.php`: large pre-populated search form, empty-query prompt state, result count heading + subline, product grid (Product_Card), empty-state card when no products, "Developers matching {query}" vendor card section (conditional), WooCommerce pagination
    - _Requirements: 7.1–7.7_
  - [x] 10.3 Write property test for search result count accuracy
    - **Property 5: Rendered result count `n` must equal `count(wc_get_products(['s' => $query]))` for any non-empty query**
    - **Validates: Requirements 7.3, 22.5**

- [x] 11. Checkpoint — Verify product, shop, and search pages render correctly
  - Ensure all tests pass, ask the user if questions arise.

- [x] 12. Implement cart and checkout pages
  - [x] 12.1 Write `app/View/Composers/CartComposer.php`: guest check → injects `isGuest: true`; logged-in → resolves `WC()->cart`, iterates items, computes `$subtotalMinor`
    - _Requirements: 8.1–8.7_
  - [x] 12.2 Write `resources/views/cart.blade.php`: guest state (login CTA), empty-cart state, items list (thumbnail/name/plan/author/billing label/price/remove/quantity stepper), order summary sidebar (`lg:top-28`) with subtotal+total+checkout button, Chapa payment note; quantity stepper via Alpine `x-data="{ qty: N }"` with `submit()` on click, decrement disabled at 1, increment disabled at 10
    - _Requirements: 8.1–8.7_
  - [x] 12.3 Write property test for quantity stepper boundary states
    - **Property 3: For any `q` in [1..10], decrement disabled iff `q == 1`, increment disabled iff `q == 10`**
    - **Validates: Requirements 8.4, 22.3**
  - [x] 12.4 Write example-based unit test for guest cart state rendering
    - Assert guest state renders "Log in to use your cart" heading, no cart item list, no order summary
    - _Requirements: 8.1, 22.9_
  - [x] 12.5 Write `app/View/Composers/CheckoutComposer.php`: injects current user email, WC cart items for sidebar summary
    - _Requirements: 9.1–9.5_
  - [x] 12.6 Write `resources/views/checkout.blade.php` and `resources/views/woocommerce/checkout/form-checkout.blade.php`: two-column layout (email `.form-input` + WC fields vs product summary sidebar), `.form-error` validation messages, submit initiates WC/Chapa payment flow
    - _Requirements: 9.1–9.5_
  - [x] 12.7 Write property test for cart subtotal invariant
    - **Property 2: Rendered subtotal must equal `sum(price_minor × qty)` / 100 formatted to 2 decimal places for any cart item collection**
    - **Validates: Requirements 8.5, 22.2**

- [x] 13. Implement vendor pages
  - [x] 13.1 Write `app/View/Composers/VendorProfileComposer.php`: resolves vendor user + meta (cover image, avatar, bio, location, website, support URL, member-since, sales count, product count, avg rating), paginates vendor's products with filter params from `$_GET`
    - _Requirements: 10.1–10.6_
  - [x] 13.2 Write `resources/views/vendors/show.blade.php`: `rounded-3xl` profile card with cover banner (`h-40 sm:h-56`, gradient fallback), `size-24 rounded-2xl` avatar with white ring, verified badge, stat pills, bio + two-column metadata grid (location/date/website/support), "Published software" section with filter form + product grid + WC pagination
    - _Requirements: 10.1–10.6_
  - [x] 13.3 Write `app/View/Composers/VendorsListingComposer.php`: queries vendor users filtered by `q`, `sort`, `verified` GET params; paginates results
    - _Requirements: 11.1–11.6_
  - [x] 13.4 Write `resources/views/vendors/index.blade.php`: page header with eyebrow/heading/subheading, filter bar (search, sort select, verified checkbox, submit), vendor cards grid (`sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4`) with hover effects, empty-state card, WC pagination
    - _Requirements: 11.1–11.6_

- [x] 14. Implement account area (shared layout + pages)
  - [x] 14.1 Write `resources/views/account/layout.blade.php`: two-column layout on `lg` with sticky `w-56` sidebar (Settings/Wishlist/Orders/Subscriptions links + logout); active link gets `bg-teal-50 text-teal-700` + `aria-current="page"`; auth guard in Composer
    - _Requirements: 12.1–12.4_
  - [x] 14.2 Write property test for active sidebar link invariant
    - **Property 8: For any account page route, exactly one sidebar link has `bg-teal-50 text-teal-700` + `aria-current="page"`, all others do not**
    - **Validates: Requirements 12.3, 22.8**
  - [x] 14.3 Write `app/View/Composers/AccountOrdersComposer.php`: fetches current user's WC orders (limit 20, date DESC); auth guard redirects to login
    - _Requirements: 13.1–13.4_
  - [x] 14.4 Write `resources/views/account/orders.blade.php`: table (Product thumbnail+name / Order ID+copy button / Status badge / Seats / ETB amount / Date); status badge CSS per mapping; clipboard copy via `Alpine.data('clipboardCopy')` with 2000ms checkmark; empty-state with receipt icon
    - _Requirements: 13.1–13.4_
  - [x] 14.5 Write property test for order status badge colour mapping
    - **Property 7: For every status in `{completed, on-hold, failed, cancelled, processing, pending, other}`, rendered badge classes match the defined mapping exactly**
    - **Validates: Requirements 13.2, 14.2, 22.7**
  - [x] 14.6 Write `app/View/Composers/AccountSubscriptionsComposer.php` + `resources/views/account/subscriptions.blade.php`: list of WC subscriptions (name/plan/status badge/renewal date/amount); same status-badge colour mapping as orders; empty-state
    - _Requirements: 14.1–14.3_
  - [x] 14.7 Write `app/View/Composers/AccountSettingsComposer.php` + `resources/views/account/settings.blade.php`: settings form in `rounded-2xl border` card; Name+Email two-column row; Password section (current/new/confirm); submit updates WP user profile via `woocommerce_save_account_details_errors`; teal success notice auto-dismisses after 4000ms via Alpine `x-show + setTimeout`; `.form-error` on invalid fields
    - _Requirements: 15.1–15.5_
  - [x] 14.8 Write `resources/views/account/wishlist.blade.php` + `app/View/Composers/WishlistComposer.php`: grid of wishlisted products using Product_Card; empty-state; auth guard; wishlist toggle POSTs to `/mh/v1/wishlist/toggle`
    - _Requirements: 6.6, 12.1_

- [x] 15. Implement auth pages
  - [x] 15.1 Write `resources/views/auth/login.blade.php`: `max-w-md mx-auto px-5 py-16` centered form; Email+Password `.form-input`; "Forgot password?" link; "Keep me signed in" checkbox; `.btn-dark` submit; `.form-error` inline messages; cross-link to register
    - _Requirements: 16.1–16.7_
  - [x] 15.2 Write `resources/views/auth/register.blade.php`: Name+Email+Password+Confirm Password inputs; `.btn-primary` submit; `.form-error` messages; cross-link to login
    - _Requirements: 16.1–16.7_
  - [x] 15.3 Write `resources/views/auth/forgot-password.blade.php`: Email input + submit; confirmation message on valid email
    - _Requirements: 16.4_
  - [x] 15.4 Write `resources/views/auth/reset-password.blade.php`: Password+Confirm Password inputs + submit; `.form-error` messages
    - _Requirements: 16.5, 16.6_

- [x] 16. Implement order lookup page
  - [x] 16.1 Write `app/View/Composers/OrderLookupComposer.php`: handles both GET (form display) and POST (lookup); sanitizes email + ref; queries `wc_get_orders()` by `billing_email` + `_mh_public_ref` meta; injects `$result` (order details incl. license key) or `$error`; implements case-insensitive email matching
    - _Requirements: 17.1–17.5, 22.11_
  - [x] 16.2 Write `resources/views/order-lookup.blade.php`: `max-w-xl` centered container; teal magnifying-glass icon + heading + description; lookup form in `rounded-lg border border-zinc-200 bg-zinc-50 p-6` card (email + ref `.form-input`); order result display (name/status/license key/date) when found; `.form-error` inline for invalid or unmatched submissions; form values preserved on re-render
    - _Requirements: 17.1–17.5_
  - [x] 16.3 Write property test for order lookup round-trip
    - **Property 9: For any WC order with billing email + public reference stored in DB, submitting that (email, ref) pair must return `found: true` with correct product name, status, and license key**
    - **Validates: Requirements 17.3, 22.9**

- [x] 17. Implement WooCommerce overrides and template resolution
  - [x] 17.1 Write `resources/views/woocommerce/myaccount/my-account.blade.php` that redirects to the custom `page-account.php` URL, ensuring WC's my-account page defers to the theme's account layout
    - _Requirements: 21.1–21.2_
  - [x] 17.2 Write example-based integration test for WooCommerce template override resolution
    - Assert `resources/views/woocommerce/checkout/form-checkout.blade.php` is loaded (not the WC default) when WordPress resolves the checkout template
    - _Requirements: 21.1–21.2, 22.10_
  - [x] 17.3 Write WordPress PHP template files (`front-page.php`, `archive-product.php`, `taxonomy-product_cat.php`, `single-product.php`, `search.php`, `page-cart.php`, `page-checkout.php`, `page-vendors.php`, `single-mh_vendor.php`, `page-account.php`, `page-wishlist.php`, `page-orders.php`, `page-subscriptions.php`, `page-settings.php`, `page-order-lookup.php`, `page-login.php`, `page-register.php`, `page-forgot-password.php`, `page-reset-password.php`) — each a one-liner that calls `sage()` to delegate to the Blade view
    - _Requirements: 20.2_

- [x] 18. Checkpoint — Verify full theme renders all pages and WC integration works
  - Ensure all tests pass, ask the user if questions arise.

- [x] 19. Implement pure helper functions and finalize test setup
  - [x] 19.1 Write `app/helpers.php` (autoloaded via `composer.json`) with pure functions: `compute_mh_discount_pct(int $price, int $compareAt): int`, `compute_mh_cart_subtotal(array $items): int`, `mh_status_badge_classes(string $status): string`
    - _Requirements: 19.2, 8.5, 13.2, 22.1–22.2, 22.7_
  - [x] 19.2 Wire the `mh_license_key` provisioning: add `woocommerce_order_status_completed` action hook in `ThemeServiceProvider` that calls the Keygen API and stores the license key as `_mh_license_key` order item meta
    - _Requirements: 13.1_
  - [x] 19.3 Write `tests/properties/DiscountPercentageTest.php` using `eris/eris` + `WP_UnitTestCase` (Property 1)
    - **Property 1: Discount percentage calculation**
    - **Validates: Requirements 19.2, 22.1**
  - [x] 19.4 Write `tests/properties/CartSubtotalTest.php` (Property 2)
    - **Property 2: Cart subtotal invariant**
    - **Validates: Requirements 8.5, 22.2**
  - [x] 19.5 Write `tests/properties/QuantityStepperTest.php` (Property 3)
    - **Property 3: Quantity stepper boundary states**
    - **Validates: Requirements 8.4, 22.3**
  - [x] 19.6 Write `tests/properties/FilterUrlRoundTripTest.php` (Property 4)
    - **Property 4: Filter URL round-trip**
    - **Validates: Requirements 5.9, 22.4**
  - [x] 19.7 Write `tests/properties/SearchResultCountTest.php` (Property 5)
    - **Property 5: Search result count accuracy**
    - **Validates: Requirements 7.3, 22.5**
  - [x] 19.8 Write `tests/properties/ConditionalSectionTest.php` (Property 6)
    - **Property 6: Conditional section rendering**
    - **Validates: Requirements 4.3, 4.4, 22.6**
  - [x] 19.9 Write `tests/properties/StatusBadgeTest.php` (Property 7)
    - **Property 7: Order status badge colour mapping**
    - **Validates: Requirements 13.2, 14.2, 22.7**
  - [x] 19.10 Write `tests/properties/ActiveSidebarLinkTest.php` (Property 8)
    - **Property 8: Active sidebar link invariant**
    - **Validates: Requirements 12.3, 22.8**
  - [x] 19.11 Write `tests/properties/OrderLookupRoundTripTest.php` (Property 9)
    - **Property 9: Order lookup round-trip**
    - **Validates: Requirements 17.3, 22.9**

- [x] 20. Final checkpoint — All tests pass, theme complete
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for a faster MVP
- All PHP must target PHP 8.2+ with strict types, constructor property promotion, and return type declarations per project conventions
- The theme uses `eris/eris` (not Pest) for property-based tests, integrated with `WP_UnitTestCase` via `Eris\TestTrait`; minimum 100 iterations per property
- Property test assertions include the tag comment format: `Feature: sage-woocommerce-theme, Property {N}: {property_text}`
- Livewire is explicitly excluded; all interactivity uses Alpine.js only
- WooCommerce AJAX cart updates fall back to a full page reload if the AJAX fragment approach is not available
- All Composer classes must guard against `WC()` being null (WooCommerce inactive)
- After modifying PHP files, run `vendor/bin/pint --dirty --format agent` to apply code style

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1"] },
    { "id": 1, "tasks": ["2.1", "2.2", "2.3", "3.1", "3.2", "3.3"] },
    { "id": 2, "tasks": ["5.1", "5.2", "5.3", "6.1", "6.4", "19.1"] },
    { "id": 3, "tasks": ["6.2", "6.3", "6.5", "7.1", "8.1", "9.1", "10.1", "12.1", "12.5", "13.1", "13.3", "14.3", "16.1"] },
    { "id": 4, "tasks": ["7.2", "8.2", "8.3", "9.2", "10.2", "12.2", "12.6", "13.2", "13.4", "14.1", "14.6", "14.7", "14.8", "15.1", "15.2", "15.3", "15.4", "16.2", "17.1", "17.3", "19.2"] },
    { "id": 5, "tasks": ["7.3", "8.4", "10.3", "12.3", "12.4", "12.7", "14.2", "14.4", "14.5", "16.3", "17.2"] },
    { "id": 6, "tasks": ["19.3", "19.4", "19.5", "19.6", "19.7", "19.8", "19.9", "19.10", "19.11"] }
  ]
}
```
