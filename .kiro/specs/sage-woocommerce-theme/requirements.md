# Requirements Document

## Introduction

This feature ports the MerebHub Laravel/Blade storefront frontend into a Sage 11 (roots.io) WordPress theme that fronts WooCommerce. The existing storefront is an Ethiopian digital software marketplace with a precisely crafted design system built on Tailwind CSS v4, Alpine.js, and Plus Jakarta Sans. The goal is a pixel-perfect recreation as a Sage WordPress theme — same templates, same component hierarchy, same design tokens — with WooCommerce supplying products, cart, checkout, orders, and licensing via Keygen.

The scope covers the global layout (header + footer), fifteen page templates, two shared components (product card and toast), the full design system, WooCommerce data integration, Sage/Acorn template architecture, and an Alpine.js interactive layer.

## Glossary

- **Theme**: The Sage 11 WordPress theme being built (`sage-woocommerce-theme`).
- **Sage**: The roots.io Sage 11 starter theme, using Acorn v4, Vite, Blade, and Tailwind CSS v4.
- **Acorn**: The Laravel-on-WordPress application container used by Sage 11 for service providers, configuration, and Blade view compilation.
- **WooCommerce**: The WordPress e-commerce plugin supplying products, variations, cart, checkout, orders, and My Account pages.
- **Product**: A WooCommerce product post, where the featured image maps to the cover image, and product variations map to pricing plans.
- **Plan**: A WooCommerce product variation or simple product representing a billing option (one-time, annual subscription, monthly subscription).
- **Vendor/Author**: A WordPress user with a custom "vendor" role or a WooCommerce Vendors plugin vendor, representing the software maker.
- **Cart**: The WooCommerce session-based shopping cart.
- **Wishlist**: A saved-products list stored via a WooCommerce wishlist plugin or custom post meta.
- **Chapa**: The Ethiopian payment gateway integrated with WooCommerce for checkout.
- **Keygen**: The licensing provider that issues license keys after a successful WooCommerce order.
- **Design_System**: The Tailwind CSS v4 theme configuration, component classes, and typographic scale defined in `resources/css/app.css`.
- **Product_Card**: The reusable Blade component that renders a product thumbnail, title, author, category, price, and rating.
- **Toast**: The reusable Alpine.js + Blade component that displays timed, dismissible success or error notifications.
- **Template_Hierarchy**: The WordPress template resolution order (index.php → single.php → archive.php → page.php etc.) that Sage 11 maps to Blade views in `resources/views/`.
- **WC_Override**: A Blade file placed under `resources/views/woocommerce/` that overrides a default WooCommerce template.
- **ETB**: Ethiopian Birr, the currency displayed for all prices.


## Requirements

---

### Requirement 1: Design System

**User Story:** As a theme developer, I want the Tailwind CSS v4 design system configured identically to the Laravel app, so that every page renders with the correct fonts, colors, spacing, and component classes without manual overrides.

#### Acceptance Criteria

1. THE Theme SHALL load Plus Jakarta Sans at weights 400, 500, 600, 700, and 800 from Bunny Fonts via a `<link>` tag in the `<head>`.
2. THE Design_System SHALL define `--font-sans` as `'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif` in the Tailwind CSS v4 `@theme` block.
3. THE Design_System SHALL define `--color-brand` as `#16cabd` in the Tailwind CSS v4 `@theme` block.
4. THE Design_System SHALL expose a `.btn-primary` component class that applies `bg-teal-400`, `text-zinc-950`, `font-extrabold`, `rounded-lg`, `px-5 py-3`, `text-sm`, `inline-flex items-center justify-center gap-2`, `transition`, `hover:bg-teal-300`, and `disabled:cursor-not-allowed disabled:opacity-50`.
5. THE Design_System SHALL expose a `.btn-dark` component class that applies `bg-zinc-950`, `text-white`, `font-extrabold`, `rounded-lg`, `px-5 py-3`, `text-sm`, `inline-flex items-center justify-center gap-2`, `transition`, and `hover:bg-teal-700`.
6. THE Design_System SHALL expose a `.form-input` component class that applies `w-full rounded-lg border border-zinc-300 bg-white px-4 py-3 text-sm outline-none transition placeholder:text-zinc-400 focus:border-teal-500 focus:ring-4 focus:ring-teal-500/10`.
7. THE Design_System SHALL expose a `.form-label` component class that applies `mb-2 block text-sm font-bold text-zinc-800`.
8. THE Design_System SHALL expose a `.form-error` component class that applies `mt-1.5 text-xs font-semibold text-rose-600`.
9. THE Theme SHALL set a maximum content width of 1500px via `max-w-[1500px]` with `px-5 lg:px-8` horizontal padding on all full-width page containers.
10. THE Design_System SHALL define `::selection` background as `#99f6e4` and color as `#18181b`.
11. THE Design_System SHALL set `[x-cloak]` to `display: none !important` so Alpine.js elements are hidden before initialization.
12. WHEN Vite compiles assets, THE Theme SHALL produce a single `app.css` and `app.js` bundle referenced via `@vite` directive in the `<head>`.

---

### Requirement 2: Global Layout — Header

**User Story:** As a site visitor, I want a sticky header with search, wishlist, cart, and account controls, so that I can navigate and shop from any page without scrolling back to the top.

#### Acceptance Criteria

1. THE Theme SHALL render a `<header>` element that is sticky at the top of the viewport with `z-40`, a `bg-white/95 backdrop-blur` background, and a `border-b border-zinc-200` separator.
2. THE Theme SHALL render the MerebHub logo (SVG) and site name as a link to the homepage within the header.
3. THE Theme SHALL render a desktop search form targeting the WooCommerce search URL, with a magnifying-glass icon on the left and a keyboard shortcut badge on the right showing "Ctrl+K" on Windows/Linux or "⌘+K" on macOS.
4. WHEN the user presses Ctrl+K (or ⌘+K on macOS), THE Theme SHALL focus the desktop search input and prevent the browser default action.
5. THE Theme SHALL render a wishlist icon link; WHEN the wishlist contains one or more items, THE Theme SHALL render a filled heart icon; WHEN the wishlist is empty, THE Theme SHALL render an outlined heart icon.
6. THE Theme SHALL render a cart icon link; WHEN the WooCommerce cart contains one or more distinct line items, THE Theme SHALL render a numeric badge showing the count over the cart icon.
7. WHEN a WordPress user is logged in, THE Theme SHALL render an account dropdown button showing a user-circle icon, "My Account" label, and chevron; WHEN clicked, THE Theme SHALL show a popover containing the user's name, email, links to Orders, Subscriptions, Account Settings, Sell Software, and a logout form.
8. WHEN no WordPress user is logged in, THE Theme SHALL render a login link with a user-circle icon and "Login" label in place of the account dropdown.
9. THE Theme SHALL render a mobile hamburger button visible on screens narrower than `lg` breakpoint; WHEN tapped, THE Theme SHALL show a slide-down mobile navigation panel containing a search form and links to Discover, Store, New Arrivals, Best Sellers, Deals, Developers, Sell Software, Wishlist, and Cart.
10. WHEN the account dropdown is open and the user clicks outside it, THE Theme SHALL close the dropdown.
11. THE Theme SHALL render the header using Alpine.js `x-data` with `mobileOpen` and `accountOpen` boolean state.

---

### Requirement 3: Global Layout — Footer

**User Story:** As a site visitor, I want a consistent dark footer with navigation links, so that I can find key sections of the site from any page.

#### Acceptance Criteria

1. THE Theme SHALL render a `<footer>` with `bg-zinc-950 text-zinc-300` and a `border-t border-zinc-200` separator, applied via `mt-16`.
2. THE Theme SHALL render four columns inside the footer using a CSS grid (`sm:grid-cols-2 lg:grid-cols-4`): Brand column (logo + tagline), Marketplace column (Browse all, New arrivals, Deals), Developers column (Submit software, Order lookup), and Your account column (Previous orders, Wishlist, Cart).
3. THE Theme SHALL constrain the footer inner content to `max-w-[1500px]` with `px-5 lg:px-8` padding.
4. THE Theme SHALL render all footer link text in `text-zinc-300` and column headings in `text-sm font-bold text-white`.

---

### Requirement 4: Home Page

**User Story:** As a shopper, I want a rich home page with a category strip, hero section, deals bar, ranked top sellers, featured grid, and a full product catalog, so that I can discover Ethiopian software at a glance.

#### Acceptance Criteria

1. THE Theme SHALL render a horizontal scrollable category strip below the header showing WooCommerce product categories with matching icons; each item SHALL link to the shop archive filtered by that category.
2. THE Theme SHALL render a full-width hero section with a background image, a dark left-to-right gradient overlay, headline text, subheadline, and a primary CTA button linking to the shop archive.
3. THE Theme SHALL render a "deals bar" showing up to five discounted WooCommerce products in a horizontal strip; each item SHALL show the product's featured image, name, author, and discounted price in `text-rose-600`; WHEN no discounted products exist, THE Theme SHALL omit the deals bar entirely.
4. WHEN at least one product is available in the top-sellers list, THE Theme SHALL render a "Top selling this week" section with a numbered ranked list showing up to twelve products with thumbnail, name, rating, category, platform, and price.
5. WHEN at least one featured product exists, THE Theme SHALL render a "Made in Ethiopia" section showing up to four featured products using the Product_Card component.
6. THE Theme SHALL render a main catalog grid below the featured section showing all published WooCommerce products using the Product_Card component, with responsive columns (`sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4`).
7. WHEN the catalog or a section has no products, THE Theme SHALL render an empty-state placeholder with a descriptive icon, heading, and link to the store rather than an empty or broken grid.

---

### Requirement 5: Shop / Store Page

**User Story:** As a shopper, I want a store page with collection tabs, search, sort, and filter controls, and a paginated product grid, so that I can browse and narrow down software efficiently.

#### Acceptance Criteria

1. THE Theme SHALL render a dark banner at the top of the store page with `bg-zinc-950 text-white`, a teal-300 eyebrow label "MerebHub Store", a dynamic heading, and a subheading.
2. THE Theme SHALL render collection tabs below the dark banner for All Software, New Arrivals, Best Sellers, and Deals; the active tab SHALL have `bg-teal-50 text-teal-800` and an `aria-current="page"` attribute.
3. THE Theme SHALL render a search input, a Sort dropdown button that opens a popover, and a Filters dropdown button that opens a wider popover containing Category and Platform selects; all controls SHALL be inside a single `<form>` that submits via GET.
4. WHEN sort or filter popovers are open and the user clicks outside them, THE Theme SHALL close the popover via Alpine.js `@click.away`.
5. THE Theme SHALL render the product count as `{n} products` above the grid; WHEN active filters exist, THE Theme SHALL render a "Clear filters" link.
6. THE Theme SHALL render the product grid with responsive columns (`sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4`) using the Product_Card component.
7. WHEN no products match the current filters, THE Theme SHALL render a full-width empty-state card with a magnifying-glass icon, descriptive text, and a "Clear filters" button.
8. THE Theme SHALL render WooCommerce pagination links below the product grid.
9. WHEN a URL query parameter state (`q`, `category`, `platform`, `sort`) is present, THE Theme SHALL pre-populate the corresponding form controls to reflect the current filter state on page load.

---

### Requirement 6: Single Product Page

**User Story:** As a shopper, I want a detailed product page showing the cover image, plans, add-to-cart form, wishlist toggle, and related products, so that I can evaluate a product and add it to my cart.

#### Acceptance Criteria

1. THE Theme SHALL render a breadcrumb navigation showing "Discover" and the product category above the product content area.
2. THE Theme SHALL render the product's featured image in a `16/10` aspect-ratio container with `rounded-lg`.
3. THE Theme SHALL render the product name as an `<h1>`, the tagline, the author name (linked to vendor profile when the vendor is active and public), and a star rating with numeric value and review count.
4. WHEN the product has at least one active WooCommerce variation (plan), THE Theme SHALL render a plan `<select>` labelled "Choose a plan" showing each plan's name and price in ETB, followed by an "Add to cart" button using `.btn-primary`.
5. WHEN the product has no active plans, THE Theme SHALL render an amber warning box stating the product is not currently available for purchase instead of the add-to-cart form.
6. WHEN a logged-in user has the product in their wishlist, THE Theme SHALL render a filled-heart wishlist button with `bg-teal-50 border-teal-300 text-teal-700`; WHEN the product is not wishlisted, THE Theme SHALL render an outlined-heart button.
7. WHEN no user is logged in, THE Theme SHALL render the wishlist button as a link to the wishlist page.
8. THE Theme SHALL render a trust badge below the plan selector stating "License delivered automatically after payment" with a key icon.
9. THE Theme SHALL render an "About this software" section with the product's full description and a "Why MerebHub?" sidebar listing Secure Chapa checkout, Immediate license key, and Official Partner.
10. WHEN at least one related product exists in the same category, THE Theme SHALL render a "More in {category}" section with up to four Product_Card components.

---

### Requirement 7: Search Results Page

**User Story:** As a shopper, I want a search results page with a prominent search bar, result count, product grid, and developer matches, so that I can quickly find the software or maker I am looking for.

#### Acceptance Criteria

1. THE Theme SHALL render a large search form (`h-14`, `rounded-xl`) at the top of the search results page pre-populated with the current query string.
2. WHEN the search query is empty, THE Theme SHALL render a prompt state with a centered card showing a magnifying-glass icon, a heading, and a "Browse the catalog" button.
3. WHEN the search query is non-empty, THE Theme SHALL render a result count heading as `{n} results` and a subline "Showing the most popular matches first".
4. THE Theme SHALL render the product results grid with responsive columns (`sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4`) using the Product_Card component.
5. WHEN no products match the query, THE Theme SHALL render a full-width empty-state card with the query echoed in the heading and a "Browse all software" button.
6. WHEN at least one vendor matches the query, THE Theme SHALL render a "Developers matching {query}" section below the product grid, showing each matching vendor as a card with avatar, name, verified badge, tagline, product count, and an arrow icon.
7. THE Theme SHALL render WooCommerce pagination links below the product results grid.

---

### Requirement 8: Cart Page

**User Story:** As a shopper, I want a cart page showing my selected items, quantity controls, and an order summary with a checkout button, so that I can review and complete my purchase.

#### Acceptance Criteria

1. WHEN no user is logged in, THE Theme SHALL render a guest state with a cart icon, "Log in to use your cart" heading, and buttons to log in or explore deals.
2. WHEN a logged-in user's WooCommerce cart is empty, THE Theme SHALL render an empty-cart state with a cart icon, heading, description, and an "Explore Deals" button.
3. WHEN a logged-in user has items in the WooCommerce cart, THE Theme SHALL render each line item with product thumbnail, product name and plan, author, billing model label, price in ETB, a remove button, and a quantity stepper.
4. THE Theme SHALL render the quantity stepper as a row of decrement button, numeric display, and increment button; the decrement button SHALL be disabled when quantity equals 1; the increment button SHALL be disabled when quantity equals 10.
5. THE Theme SHALL render an order summary sidebar (sticky at `lg:top-28`) showing subtotal, a "Secure Chapa checkout" payment note, total amount in ETB, and a "Complete Order" button that submits to WooCommerce checkout.
6. THE Theme SHALL render a note below the checkout button: "Payment information is handled and secured by Chapa."
7. WHEN the cart is updated (quantity change or remove), THE Theme SHALL reflect the new subtotal without a full page reload where WooCommerce AJAX cart is available; otherwise the page SHALL reload to show updated state.

---

### Requirement 9: Checkout Page

**User Story:** As a shopper, I want a minimal checkout page with an email form and a product summary, so that I can confirm my order and be redirected to the Chapa payment gateway.

#### Acceptance Criteria

1. THE Theme SHALL render a checkout page with a two-column layout on large screens: a main form column and a product summary sidebar.
2. THE Theme SHALL render an email input field with `.form-input` styling, labelled "Email address", required, and pre-populated with the logged-in user's email when available.
3. THE Theme SHALL render the WooCommerce order summary sidebar showing product names, plans, quantities, and the order total in ETB.
4. WHEN the checkout form is submitted, THE Theme SHALL initiate the WooCommerce/Chapa payment flow.
5. THE Theme SHALL render validation error messages beneath each invalid field using `.form-error` class.

---

### Requirement 10: Author / Vendor Profile Page

**User Story:** As a shopper, I want a vendor profile page showing the maker's cover banner, avatar, stats, bio, and product grid with filters, so that I can explore all software from a specific Ethiopian developer.

#### Acceptance Criteria

1. THE Theme SHALL render a profile card with a `rounded-3xl` container, a cover banner (`h-40 sm:h-56`) showing either a cover image or a gradient from `zinc-950` through `teal-950` to `teal-600`, and a `size-24 rounded-2xl` avatar with a white ring.
2. WHEN the vendor is verified, THE Theme SHALL render a "Verified" badge with `bg-teal-50 text-teal-700` and a check-badge icon next to the vendor's name.
3. THE Theme SHALL render vendor stats (product count, average rating, and public sales count when enabled) as pill-shaped badges with `bg-zinc-50 ring-1 ring-zinc-950/5`.
4. THE Theme SHALL render the vendor's bio, location (with map-pin icon), member-since date, website URL, and support URL in a two-column metadata grid below the main bio.
5. THE Theme SHALL render a "Published software" section with a filter form (search input, category select, sort select) and a product grid using the Product_Card component with responsive columns (`sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4`).
6. THE Theme SHALL render WooCommerce pagination links below the vendor's product grid.

---

### Requirement 11: Vendors Listing Page

**User Story:** As a shopper, I want a vendors listing page with a hero header, filter bar, and vendor card grid, so that I can discover and filter Ethiopian software makers.

#### Acceptance Criteria

1. THE Theme SHALL render a page header with a teal-700 eyebrow "Ethiopian developers", a large heading "Meet the makers behind the software", and a descriptive subheading.
2. THE Theme SHALL render a filter bar with a search input (name="q"), a sort select (Newest, Most products, Most sales, Highest rated), a "Verified" checkbox (name="verified"), and an "Apply filters" submit button.
3. THE Theme SHALL render the vendors grid with responsive columns (`sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4`); each vendor card SHALL show avatar or placeholder icon, vendor name, verified badge when applicable, tagline, product count, average rating, and public sales count when enabled.
4. WHEN a vendor card is hovered, THE Theme SHALL apply `hover:-translate-y-0.5 hover:border-teal-300 hover:shadow-lg` and change the vendor name color to `text-teal-700`.
5. WHEN no vendors match the current filters, THE Theme SHALL render a full-width empty-state with a user-group icon and "No developers match those filters" heading.
6. THE Theme SHALL render WooCommerce pagination links below the vendor grid.

---

### Requirement 12: Account Pages — Shared Sidebar Layout

**User Story:** As a logged-in customer, I want a consistent account area with a sidebar navigation, so that I can move between orders, subscriptions, settings, and wishlist without losing my place.

#### Acceptance Criteria

1. THE Theme SHALL render a two-column layout for all account pages on `lg` screens: a sticky `w-56` sidebar on the left and a main content area on the right.
2. THE Theme SHALL render the sidebar navigation with links to Account Settings, Wishlist, Orders, Subscriptions, and a Logout button.
3. THE Theme SHALL apply `bg-teal-50 text-teal-700` to the sidebar link that corresponds to the current page and `aria-current="page"` on that link.
4. WHEN the current user is not authenticated, THE Theme SHALL redirect account pages to the WordPress login page.

---

### Requirement 13: Account Page — Orders

**User Story:** As a logged-in customer, I want an orders page listing my WooCommerce orders in a table, so that I can review past purchases and copy my order IDs.

#### Acceptance Criteria

1. THE Theme SHALL render a table with columns: Product (thumbnail + name), Order (public ID + copy button), Status (coloured badge), Seats (quantity), Amount (in ETB), and Date.
2. THE Theme SHALL colour the status badge according to the WooCommerce order status: completed → `bg-emerald-50 text-emerald-700`, on-hold → `bg-amber-50 text-amber-800`, failed/cancelled → `bg-rose-50 text-rose-700`, processing → `bg-sky-50 text-sky-700`, default → `bg-zinc-50 text-zinc-700`.
3. WHEN the user clicks the copy button next to an order ID, THE Theme SHALL copy the full order ID to the clipboard and temporarily swap the clipboard icon for a checkmark icon for 2000ms.
4. WHEN the orders list is empty, THE Theme SHALL render an empty-state with a receipt icon, "No previous orders" heading, and a prompt to browse the store.

---

### Requirement 14: Account Page — Subscriptions

**User Story:** As a logged-in customer, I want a subscriptions page listing my active and past WooCommerce subscriptions, so that I can track renewal dates and statuses.

#### Acceptance Criteria

1. THE Theme SHALL render a list of WooCommerce subscriptions for the current user, showing product name, plan name, status badge, renewal date, and amount per cycle in ETB.
2. THE Theme SHALL colour subscription status badges using the same colour mapping as order statuses (Requirement 13, criterion 2).
3. WHEN the subscriptions list is empty, THE Theme SHALL render an empty-state with a descriptive icon and "No active subscriptions" heading.

---

### Requirement 15: Account Page — Account Settings

**User Story:** As a logged-in customer, I want an account settings page where I can update my name, email, and password, so that I can keep my account information current.

#### Acceptance Criteria

1. THE Theme SHALL render a settings form within a `rounded-2xl border border-zinc-200 bg-white p-6 sm:p-8 shadow-sm` card.
2. THE Theme SHALL render a Name input and an Email input in a two-column `sm:grid-cols-2` layout, each using `.form-input`, pre-populated from the current user profile.
3. THE Theme SHALL render a Password section with Current Password, New Password, and Confirm New Password fields; the New Password and Confirm fields SHALL span two columns on `sm` screens.
4. WHEN the form is submitted with valid data, THE Theme SHALL update the WordPress user profile and display a teal success notice that auto-dismisses after 4000ms via Alpine.js `x-show` + `setTimeout`.
5. WHEN the form is submitted with invalid data, THE Theme SHALL re-render the form with inline `.form-error` messages beneath each invalid field.

---

### Requirement 16: Auth Pages

**User Story:** As a new or returning visitor, I want clean minimal login, register, forgot-password, and reset-password pages, so that I can authenticate or recover my account without friction.

#### Acceptance Criteria

1. THE Theme SHALL render all auth pages within a `max-w-md mx-auto px-5 py-16` centered container using the global storefront layout.
2. THE Theme SHALL render the login form with Email and Password inputs (`.form-input`), a "Forgot password?" link aligned right, a "Keep me signed in" checkbox, and a "Sign in" submit button using `.btn-dark`.
3. THE Theme SHALL render the registration form with Name, Email, Password, and Confirm Password inputs, and a "Create account" submit button using `.btn-primary`.
4. THE Theme SHALL render the forgot-password form with an Email input and a submit button; WHEN a valid email is submitted, THE Theme SHALL display a confirmation message.
5. THE Theme SHALL render the reset-password form with Password and Confirm Password inputs and a submit button.
6. THE Theme SHALL render inline `.form-error` messages beneath each invalid field on all auth forms.
7. THE Theme SHALL render cross-links between Login and Register pages ("New to MerebHub? Create an account" and "Already registered? Sign in") in `text-teal-700 font-extrabold`.

---

### Requirement 17: Order Lookup Page

**User Story:** As a customer without an account, I want a public order lookup page where I can find my order by email and order reference, so that I can retrieve my license key without logging in.

#### Acceptance Criteria

1. THE Theme SHALL render a centered lookup form within a `max-w-xl` container with a teal magnifying-glass icon, "Look up your order" heading, and a description.
2. THE Theme SHALL render the lookup form inside a `rounded-lg border border-zinc-200 bg-zinc-50 p-6` card with a "Purchase email" input (type="email") and an "Order reference" input, both using `.form-input`.
3. WHEN a valid email and order reference are submitted that match a WooCommerce order, THE Theme SHALL display the order details including product name, status, license key (if issued), and order date.
4. WHEN the submitted email or order reference does not match any order, THE Theme SHALL display an inline `.form-error` message and re-render the form with the submitted values pre-populated.
5. IF the submitted email is not a valid email address, THEN THE Theme SHALL display a `.form-error` message without submitting the lookup request.

---

### Requirement 18: Toast Notification Component

**User Story:** As a site user, I want toast notifications that appear and auto-dismiss, so that I receive clear feedback when actions succeed or fail.

#### Acceptance Criteria

1. THE Theme SHALL render success toasts with `bg-white text-teal-950 ring-teal-700/15`, a `bg-teal-50 text-teal-700` icon container, and a check-circle icon; THE Theme SHALL render error toasts with `bg-white text-rose-950 ring-rose-700/15`, a `bg-rose-50 text-rose-700` icon container, and an exclamation-circle icon.
2. THE Theme SHALL render success toasts with `role="status" aria-live="polite"` and error toasts with `role="alert" aria-live="assertive"`.
3. WHEN a toast is rendered, THE Theme SHALL auto-dismiss it after a configurable duration (default 5000ms for success, 7000ms for error) using Alpine.js `x-init` + `setTimeout`.
4. WHEN the user clicks the close button or presses the Escape key, THE Theme SHALL immediately dismiss the toast using Alpine.js `@keydown.escape.window`.
5. THE Theme SHALL animate the toast on enter with `translate-y-1 opacity-0` → `translate-y-0 opacity-100` and on leave with `translate-y-0 opacity-100` → `-translate-y-1 opacity-0` using Alpine.js `x-transition`.
6. THE Theme SHALL render the toast container as `fixed right-4 top-20 z-50 max-w-sm pointer-events-none` with individual toasts having `pointer-events-auto`.
7. WHEN a WordPress session flash (`status`) or validation errors exist, THE Theme SHALL automatically render the appropriate toast from the global layout.

---

### Requirement 19: Product Card Component

**User Story:** As a theme developer, I want a reusable product card Blade component, so that every page that lists products renders consistently with the correct visual hierarchy.

#### Acceptance Criteria

1. THE Product_Card SHALL render a product link wrapping a `16/10` aspect-ratio image container with `rounded-lg overflow-hidden bg-zinc-100`; WHEN hovered, THE Product_Card SHALL scale the image by `group-hover:scale-[1.03]` with a 300ms transition.
2. WHEN the WooCommerce product has a compare-at (regular) price greater than its sale price, THE Product_Card SHALL render a discount badge at `left-3 top-3` inside the image container showing "Save {n}%" in `bg-white text-rose-600 font-extrabold text-xs rounded-md shadow-sm`.
3. THE Product_Card SHALL render the product name as a `text-[15px] font-extrabold text-zinc-950` truncated link that changes to `text-teal-700` on hover.
4. THE Product_Card SHALL render the author name as a link to the vendor profile (when the vendor is active and public) and the product category, separated by "·", in `text-xs font-medium text-zinc-500`.
5. THE Product_Card SHALL render the price in ETB as `text-sm font-bold text-zinc-950` aligned right of the title/author block.
6. THE Product_Card SHALL render a star rating row with an amber filled-star icon (`size-3.5`), the numeric rating in `font-bold text-zinc-700`, and the review count in `text-zinc-500`, all in `text-xs`.
7. THE Product_Card SHALL accept a `$product` prop compatible with a WooCommerce product object and SHALL apply any additional HTML attributes passed via `$attributes`.

---

### Requirement 20: WooCommerce Data Integration

**User Story:** As a theme developer, I want WooCommerce product, cart, order, and user data wired into every template via Sage/Acorn composers, so that every page displays live data without custom database queries in Blade views.

#### Acceptance Criteria

1. THE Theme SHALL register a Sage Acorn view composer for every template that requires WooCommerce data, passing pre-fetched variables into the Blade view (e.g., `$products`, `$vendor`, `$cartItems`, `$orders`).
2. THE Theme SHALL use WooCommerce's `wc_get_products()`, `WC()->cart`, `wc_get_customer_orders()`, and related WooCommerce API functions inside view composers rather than direct database queries.
3. THE Theme SHALL map WooCommerce product fields to template variables as follows: featured image URL → `$product->coverUrl()` equivalent via a helper or model method, product title → `name`, short description → `tagline`, description → `description`, variations → `activePlans`.
4. WHEN WooCommerce is not active, THE Theme SHALL degrade gracefully by rendering an admin notice and falling back to an empty product list rather than throwing a fatal error.
5. THE Theme SHALL register WooCommerce template overrides in `resources/views/woocommerce/` following the WooCommerce template override convention, so that custom cart, checkout, and My Account templates are used instead of WooCommerce defaults.

---

### Requirement 21: Sage Theme Architecture

**User Story:** As a theme developer, I want the theme to comply with Sage 11 conventions and the WordPress template hierarchy, so that WordPress resolves templates correctly and the Vite build pipeline works as expected.

#### Acceptance Criteria

1. THE Theme SHALL place all Blade view templates in `resources/views/` following Sage 11 naming: `index.blade.php`, `single.blade.php`, `archive.blade.php`, `page.blade.php`, `front-page.blade.php`, `search.blade.php`, `404.blade.php`.
2. THE Theme SHALL declare WooCommerce support in `functions.php` via `add_theme_support('woocommerce')` with appropriate `thumbnail_image_width`, `single_image_width`, and `product_grid` arguments.
3. THE Theme SHALL configure the Vite build to process `resources/css/app.css` (Tailwind CSS v4) and `resources/js/app.js` (Alpine.js bootstrap) and output to the `public/` directory.
4. THE Theme SHALL register Alpine.js in `resources/js/app.js` by importing and starting Alpine as `import Alpine from 'alpinejs'; window.Alpine = Alpine; Alpine.start()`.
5. THE Theme SHALL register Heroicons as Blade components via a Sage/Acorn service provider so that `<x-heroicon-o-*>` and `<x-heroicon-s-*>` components resolve to the correct SVG output.
6. THE Theme SHALL use Sage 11 `app/View/Composers/` directory for all view composers, each registered through the Acorn application.
7. THE Theme SHALL not use Livewire; all interactive behaviour SHALL be implemented with Alpine.js. WHERE a feature required Livewire in the Laravel app (e.g., home catalog), THE Theme SHALL replace it with static server-rendered Blade output or Alpine.js + WP AJAX.

---

### Requirement 22: Correctness Properties

**User Story:** As a theme developer, I want property-based and example-based tests that verify the correctness of the design system, data mapping, and interactive component logic, so that regressions are caught automatically.

#### Acceptance Criteria

**Property: Discount percentage calculation is always a whole number between 1 and 99**
1. FOR ALL WooCommerce products where `sale_price > 0` AND `regular_price > sale_price`, THE Product_Card SHALL compute the discount badge percentage as `round((1 - sale_price / regular_price) * 100)`, which SHALL always be an integer in the range [1, 99].

**Property: Cart subtotal invariant**
2. FOR ALL non-empty WooCommerce carts, THE Theme SHALL display a subtotal equal to the sum of `(line_item_price × line_item_quantity)` across all cart items, expressed in ETB with two decimal places.

**Property: Quantity stepper bounds**
3. FOR ALL cart line item quantities `q`, THE Theme SHALL disable the decrement button WHEN `q = 1` and disable the increment button WHEN `q = 10`; for all `q` in [2, 9], both buttons SHALL be enabled.

**Property: Filter state round-trip on shop page**
4. FOR ALL combinations of query parameters (`q`, `category`, `platform`, `sort`) submitted to the shop archive URL, THE Theme SHALL re-populate the corresponding form controls (`<input name="q">`, `<select name="category">`, `<select name="platform">`, `<select name="sort">`) with the same values on page load.

**Property: Search result count invariant**
5. FOR ALL search queries, THE Theme SHALL display a result count `n` where `n` equals the total number of WooCommerce products returned by the search query (i.e., `count(results) = displayed_count`).

**Property: Design token class completeness**
6. FOR ALL `.btn-primary`, `.btn-dark`, `.form-input`, `.form-label`, and `.form-error` occurrences in compiled CSS, every declared property SHALL match the corresponding `@apply` rule defined in `resources/css/app.css`.

**Example: Toast ARIA roles**
7. WHEN a success toast is rendered, THE Toast component SHALL have `role="status"` and `aria-live="polite"`; WHEN an error toast is rendered, THE Toast component SHALL have `role="alert"` and `aria-live="assertive"`.

**Example: Active sidebar nav link**
8. WHEN the current WordPress page matches a sidebar nav route (Account Settings, Wishlist, Orders, Subscriptions), THE Theme SHALL apply `bg-teal-50 text-teal-700` to exactly one sidebar link and set `aria-current="page"` on that link.

**Example: Guest cart state**
9. WHEN a non-authenticated user visits the cart page, THE Theme SHALL render the guest-state section with a "Log in to use your cart" heading and SHALL NOT render the cart item list or order summary.

**Example: WooCommerce template override resolution**
10. WHEN WordPress resolves the cart, checkout, or My Account template, THE Theme SHALL load the corresponding Blade override from `resources/views/woocommerce/` rather than the WooCommerce default PHP template.

**Edge case: Order lookup email case-insensitivity**
11. WHEN the order lookup form is submitted with an email address that differs only in letter case from the email stored on the WooCommerce order (e.g., "User@Example.com" vs "user@example.com"), THE Theme SHALL still locate and return the matching order.
