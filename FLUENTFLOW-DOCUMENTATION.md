# FluentFlow — Complete Documentation

> Plugin Name: FluentFlow — Page Builder Bridge  
> File: fluentflow-bricks-bridge.php  
> Version: 1.0.0  
> Text Domain: fluentflow-bricks-bridge

---

## Table of Contents

1. [What This Plugin Does](#1-what-this-plugin-does)
2. [Architecture & File Structure](#2-architecture--file-structure)
3. [All Features & Tokens](#3-all-features--tokens)
4. [Bricks Query Loops](#4-bricks-query-loops)
5. [Shortcode Container Loops](#5-shortcode-container-loops)
6. [Live AJAX Cart Updates](#6-live-ajax-cart-updates)
7. [FluentCart Internals We Discovered](#7-fluentcart-internals-we-discovered)
8. [Complete Build History](#8-complete-build-history)

---

## 1. What This Plugin Does

FluentFlow bridges FluentCart (a WooCommerce alternative/ecommerce plugin) with the Bricks Builder page builder. It provides:

- **Dynamic Tags** — `{ff_product_title}`, `{ff_cart_total}`, `{ff_order_status}`, etc. that render live data from FluentCart in any Bricks element.
- **Query Loops** — Bricks-native query types (`Cart Items`, `Customer Orders`) so you can loop over cart items and orders using Bricks' own loop builder.
- **Container Shortcodes** — `[ff_cart_items]` and `[ff_customer_orders]` for non-Bricks usage (Classic Editor, other page builders).
- **Checkout Form Embed** — `{ff_checkout_form}` renders FluentCart's native checkout (payment, shipping, AJAX) — not a Bricks form.
- **Live AJAX Updates** — Cart totals, item prices, subtotals, and counts update automatically when the user changes quantities, without page refresh.
- **Cart Buttons** — `{ff_add_to_cart}`, `{ff_buy_now}`, `{ff_direct_checkout}` render interactive buttons with FluentCart's JS data attributes.

---

## 2. Architecture & File Structure

```
fluentflow-bricks-bridge.php          # Main plugin file, constants, PSR-4 autoloader
src/
├── Admin/AdminDashboard.php          # Admin settings and token reference UI
├── Contracts/FeatureInterface.php    # Module contract
├── Core/Plugin.php                   # Bootstrap, module lifecycle, global hook registration
├── Data/DataFetcher.php              # Token resolver facade
├── DataProvider/
│   ├── CartData.php                  # FluentCart cart model/data access
│   └── ProductData.php               # FluentCart product model/data access
├── Integrations/
│   ├── Bricks/BricksModule.php       # Bricks dynamic tags, elements, query loops
│   ├── Bricks/TemplateOverridesModule.php
│   └── Elementor/ElementorModule.php
├── Licensing/ProModule.php
└── Shortcodes/Shortcodes.php
includes/
├── compat.php                        # Legacy class aliases for existing integrations
└── views/                            # PHP-only admin views
assets/
├── css/admin-dashboard-style.css
└── js/
    ├── admin-dashboard.js
    └── ffbb-cart-live.js             # Frontend live cart updater
```

### Bootstrap Flow

1. `fluentflow-bricks-bridge.php` registers autoloader and `init` hook
2. On `init` (priority 5), `FluentFlow\Core\Plugin::boot()` runs:
   - Instantiates built-in modules from `src/Integrations` and `src/Licensing`
   - Calls each module's `init()` method
   - Registers shortcodes via `FluentFlow\Shortcodes\Shortcodes::instance()->init()`
   - Registers AJAX handlers for `ffbb_cart_data`
   - Initializes `FluentFlow\Admin\AdminDashboard` (admin only)

### Data Boundaries

- `CartData` owns FluentCart cart lookup, cart data parsing, totals, counts, and item media resolution.
- `ProductData` owns FluentCart product ID resolution, product model lookup, and default variation lookup.
- Hook classes register WordPress/Bricks/Elementor callbacks only; business logic lives in data providers and token rendering classes.
- No API keys, database credentials, or license secrets are localized into frontend scripts.

---

## 3. All Features & Tokens

### 3.1 Product Tokens (Group: "Product")

Work on single product pages or via `[ff_product_* id="123"]`.

| Token | Shortcode | Returns | Resolver Logic |
|---|---|---|---|
| `{ff_product_title}` | `[ff_product_title]` | Post title string | `Product::find(id)->post_title` |
| `{ff_product_price}` | `[ff_product_price]` | Formatted price or range | `detail->min_price` / `detail->max_price`, formatted via `format_price()` |
| `{ff_product_sku}` | `[ff_product_sku]` | SKU string | First variant's `sku` property |
| `{ff_product_stock_status}` | `[ff_product_stock_status]` | In Stock / Out of Stock / On Backorder | `detail->stock_availability` mapped to labels |
| `{ff_product_thumbnail}` | `[ff_product_thumbnail]` | Featured image URL | `get_post_thumbnail_id()` → `wp_get_attachment_image_url()` |
| `{ff_product_description}` | `[ff_product_description]` | Truncated description (30 words) | `post_content` → `wp_trim_words()` |
| `{ff_product_url}` | `[ff_product_url]` | Permalink URL | `get_permalink(ID)` |
| `{ff_product_min_price}` | `[ff_product_min_price]` | Minimum price formatted | `detail->min_price` |
| `{ff_product_max_price}` | `[ff_product_max_price]` | Maximum price formatted | `detail->max_price` |
| `{ff_product_variation_count}` | `[ff_product_variation_count]` | Number string | `variants()->count()` |
| `{ff_product_fulfillment_type}` | `[ff_product_fulfillment_type]` | Physical / Digital | `detail->fulfillment_type` |

**Product ID Resolution Chain:**
1. Explicit `context_id` / shortcode `id=""` attribute
2. `fluent_cart_get_current_product()` function (from FluentCart)
3. `get_the_ID()` with post type check

### 3.2 Customer Tokens (Group: "Customer")

Work on any page for logged-in users, or via `[ff_customer_* id=""]`.

| Token | Shortcode | Returns | Resolver Logic |
|---|---|---|---|
| `{ff_customer_name}` | `[ff_customer_name]` | "First Last" | `$customer->first_name . ' ' . $customer->last_name` |
| `{ff_customer_email}` | `[ff_customer_email]` | Email string | `$customer->email` |
| `{ff_customer_ltv}` | `[ff_customer_ltv]` | Lifetime value (formatted) | `$customer->ltv` |
| `{ff_customer_order_count}` | `[ff_customer_order_count]` | Total orders count | `$customer->purchase_count` |
| `{ff_customer_first_order_date}` | `[ff_customer_first_order_date]` | Formatted date | `$customer->first_purchase_date` |
| `{ff_customer_last_order_date}` | `[ff_customer_last_order_date]` | Formatted date | `$customer->last_purchase_date` |
| `{ff_customer_aov}` | `[ff_customer_aov]` | Average order value | `$customer->aov` |
| `{ff_customer_photo}` | `[ff_customer_photo]` | `<img>` tag (avatar) | `$customer->photo` → Gravatar fallback via `get_avatar_url()` |
| `{ff_customer_billing}` | `[ff_customer_billing]` | Full formatted address | `$customer->billing_address` → `format_address()` (name, street, city, state, postcode, country) |
| `{ff_customer_shipping}` | `[ff_customer_shipping]` | Full formatted address | `$customer->shipping_address` → same formatter |

**Customer Resolution Chain:**
1. Explicit `context_id`
2. `CustomerResource::getCurrentCustomer()` (queries by user_id OR email, per-request cached)
3. `Customer::where('user_id', $current_user_id)->first()`

### 3.3 Order Tokens (Group: "Order")

Work inside a Bricks `Customer Orders` query loop, inside `[ff_customer_orders]`, or via `[ff_order_* id=""]`.

| Token | Shortcode | Returns | Resolver Logic |
|---|---|---|---|
| `{ff_order_id}` | `[ff_order_id]` | Order ID | `$order->id` |
| `{ff_order_total}` | `[ff_order_total]` | Total formatted | `$order->total_amount / 100` → `format_price()` |
| `{ff_order_subtotal}` | `[ff_order_subtotal]` | Subtotal formatted | `$order->subtotal / 100` |
| `{ff_order_status}` | `[ff_order_status]` | Draft / Pending / Processing / Completed / etc. | `$order->status` mapped to labels |
| `{ff_order_payment_status}` | `[ff_order_payment_status]` | Paid / Unpaid / Refunded / etc. | `ucfirst(str_replace('_', ' ', $order->payment_status))` |
| `{ff_order_payment_method}` | `[ff_order_payment_method]` | Stripe / PayPal / etc. | `$order->payment_method_title` |
| `{ff_order_currency}` | `[ff_order_currency]` | USD / EUR / etc. | `strtoupper($order->currency)` |
| `{ff_order_item_count}` | `[ff_order_item_count]` | Number of line items | `$order->order_items()->count()` |
| `{ff_order_receipt_number}` | `[ff_order_receipt_number]` | Receipt number | `$order->receipt_number` |
| `{ff_order_date}` | `[ff_order_date]` | Formatted date | `$order->created_at` → `wp_date()` |
| `{ff_order_invoice_no}` | `[ff_order_invoice_no]` | Invoice number | `$order->invoice_no` |
| `{ff_order_uuid}` | `[ff_order_uuid]` | Order UUID | `$order->uuid` |
| `{ff_order_type}` | `[ff_order_type]` | One-time / Subscription / Renewal | `$order->type` mapped to labels |

**Order Resolution Chain (inside loops):**
1. Static context (set by `[ff_customer_orders]` shortcode)
2. Bricks `ff_customer_orders` loop object (`\Bricks\Query::get_loop_object()`)
3. `$GLOBALS['fc_order']` (set by Bricks loop callback)
4. Direct DB lookup by ID

### 3.4 Cart-Level Tokens (Group: "Cart")

Work globally — they always resolve to the current visitor's cart.

| Token | Shortcode | Returns | Live Update |
|---|---|---|---|
| `{ff_cart_item_count}` | `[ff_cart_item_count]` | Total quantity of all items | ✅ Yes |
| `{ff_cart_total}` | `[ff_cart_total]` | Estimated total (formatted) | ✅ Yes |
| `{ff_cart_subtotal}` | `[ff_cart_subtotal]` | Sum of line totals | ✅ Yes |
| `{ff_cart_items_table}` | `[ff_cart_items_table]` | Full HTML table with qty controls, remove buttons, FluentCart data attributes | ❌ Uses FluentCart's own fragment system |
| `{ff_checkout_form}` | `[ff_checkout_form]` | FluentCart's native checkout form (via `[fluent_cart_checkout]`) | ❌ Full page |

### 3.5 Cart Item Tokens (Group: "Cart")

Work inside a Bricks `Cart Items` query loop or inside `[ff_cart_items]` container.

| Token | Shortcode | Returns | Live Update | Data Attrs |
|---|---|---|---|---|
| `{ff_cart_item_id}` | `[ff_cart_item_id]` | Cart entry ID | ❌ | `data-ffbb-token="item_id"` |
| `{ff_cart_item_name}` | `[ff_cart_item_name]` | Product title (linked if URL exists) | ❌ | `data-ffbb-token="name"` |
| `{ff_cart_item_variation}` | `[ff_cart_item_variation]` | Variation title (if different) | ❌ | — |
| `{ff_cart_item_image}` | `[ff_cart_item_image]` | `<img>` tag with product image | ❌ | `data-ffbb-token="image"` |
| `{ff_cart_item_price}` | `[ff_cart_item_price]` | Unit price formatted | ✅ | `data-ffbb-token="price"` |
| `{ff_cart_item_quantity}` | `[ff_cart_item_quantity]` | Quantity controls (buttons + input) with FluentCart data attrs | ✅ Input value | `data-ffbb-token="quantity"` |
| `{ff_cart_item_subtotal}` | `[ff_cart_item_subtotal]` | Line total formatted | ✅ | `data-ffbb-token="subtotal"` |
| `{ff_cart_item_url}` | `[ff_cart_item_url]` | Product view URL | ❌ | — |
| `{ff_cart_item_remove}` | `[ff_cart_item_remove]` | Remove button with FluentCart data attrs | ❌ Handled by FC | — |

**Cart Item Image Resolution Chain:**
1. `$item['featured_media']` (stored in cart data JSON by FluentCart when item was added)
2. `ProductVariation::find(object_id)->thumbnail` (queries `fct_product_meta` for `product_thumbnail` meta)
3. `$variation->product->thumbnail` (product's gallery first image URL)
4. `get_the_post_thumbnail_url($post_id)` (WordPress native featured image)
5. `Vite::getAssetUrl('images/placeholder.svg')` (FluentCart's own placeholder SVG)

### 3.6 Subscription Tokens (Group: "Subscription")

| Token | Shortcode | Returns |
|---|---|---|
| `{ff_subscription_status}` | `[ff_subscription_status]` | Active / Trialling / etc. |
| `{ff_subscription_recurring}` | `[ff_subscription_recurring]` | "$X / month" |
| `{ff_subscription_next_billing}` | `[ff_subscription_next_billing]` | Formatted next billing date |

### 3.7 Coupon Tokens (Group: "Coupon")

| Token | Shortcode | Returns |
|---|---|---|
| `{ff_coupon_code}` | `[ff_coupon_code]` | Coupon code string |
| `{ff_coupon_amount}` | `[ff_coupon_amount]` | "10%" or "$10.00" |
| `{ff_coupon_type}` | `[ff_coupon_type]` | Percentage / Fixed Amount |

### 3.8 Button Tokens (Group: "Cart")

| Token | Shortcode | Output |
|---|---|---|
| `{ff_add_to_cart}` | `[ff_add_to_cart]` | `<button class="fluent-cart-add-to-cart-button" data-fluent-cart-add-to-cart-button data-cart-id="..." data-product-id="...">` |
| `{ff_buy_now}` | `[ff_buy_now]` | `<a href="?fluent-cart=instant_checkout&item_id=..." class="fluent-cart-direct-checkout-button" data-fluent-cart-direct-checkout-button>` |
| `{ff_direct_checkout}` | `[ff_direct_checkout]` | Same structure as Buy Now |

### 3.9 Site Info Tokens (no group)

| Token | Returns |
|---|---|
| `{ff_site_name}` | `get_bloginfo('name')` |
| `{ff_site_description}` | `get_bloginfo('description')` |
| `{ff_current_year}` | `wp_date('Y')` |

### Global Tokens

Available on EVERY page:
`{ff_add_to_cart}`, `{ff_buy_now}`, `{ff_direct_checkout}`, `{ff_cart_item_count}`, `{ff_cart_total}`, `{ff_cart_subtotal}`, `{ff_cart_items_table}`, `{ff_checkout_form}`, `{ff_site_name}`, `{ff_site_description}`, `{ff_current_year}`

---

## 4. Bricks Query Loops

### 4.1 Cart Items Query (`ff_cart_items`)

**What it does:** Iterates over every item in the current visitor's cart. Each loop iteration makes standard cart-item tokens (`{ff_cart_item_name}`, `{ff_cart_item_price}`, etc.) resolve to the current item's data.

**Registered via 5 Bricks filters:**
- `bricks/setup/control_options` — adds "Cart Items" to Query Type dropdown
- `bricks/query/run` — returns cart items array from `CartHelper::getCart()->cart_data`
- `bricks/query/loop_object` — sets global `$post` from `$item['post_id']` for WP tag compat
- `bricks/query/loop_object_id` — returns `$item['post_id']` for each item
- `bricks/query/loop_object_type` — returns `'post'`

**How to use in Bricks:**
1. Add a Container element
2. Set Query → Type → **"Cart Items"**
3. Inside, place any cart item token

### 4.2 Customer Orders Query (`ff_customer_orders`)

**What it does:** Iterates over the current logged-in user's orders (types: payment, subscription), newest first, limit 50.

**Registered via 5 Bricks filters (priority 11):**
- `bricks/setup/control_options` — adds "Customer Orders" to Query Type dropdown
- `bricks/query/run` — fetches orders via `CustomerResource::getCurrentCustomer()->orders()->whereIn('type', ['payment', 'subscription'])`
- `bricks/query/loop_object` — sets `$GLOBALS['fc_order']` for each order
- `bricks/query/loop_object_id` — returns `$order->id`
- `bricks/query/loop_object_type` — returns `null` (orders are not WP posts)

**How to use in Bricks:**
1. Add a Container element
2. Set Query → Type → **"Customer Orders"**
3. Inside, use any order token + customer token

### 4.3 Customer Query (`ff_customer`)

**What it does:** Provides the current logged-in FluentCart customer as a one-item Bricks loop. Each loop iteration makes customer tokens (`{ff_customer_name}`, `{ff_customer_email}`, etc.) resolve to that customer.

**How to use in Bricks:**
1. Add a Container element
2. Set Query → Type → **"Customer"**
3. Inside, use any customer token

### 4.4 Subscriptions Query (`ff_subscriptions`)

**What it does:** Iterates over the current logged-in customer's subscriptions, newest first. Each loop iteration makes subscription tokens (`{ff_subscription_status}`, `{ff_subscription_recurring}`, `{ff_subscription_next_billing}`) resolve to the current subscription object.

**How to use in Bricks:**
1. Add a Container element
2. Set Query → Type → **"Subscriptions"**
3. Inside, use any subscription token

### 4.5 Coupons Query (`ff_coupons`)

**What it does:** Iterates over FluentCart coupons, newest first. Each loop iteration makes coupon tokens (`{ff_coupon_code}`, `{ff_coupon_amount}`, `{ff_coupon_type}`) resolve to the current coupon object.

**How to use in Bricks:**
1. Add a Container element
2. Set Query → Type → **"Coupons"**
3. Inside, use any coupon token

---

## 5. Shortcode Container Loops

For non-Bricks usage (Classic Editor, other page builders, raw shortcodes in Bricks HTML elements).

### 5.1 `[ff_cart_items]`

```
[ff_cart_items]
  <div class="cart-item">
    {ff_cart_item_image}
    <h3>{ff_cart_item_name}</h3>
    <p>{ff_cart_item_price} × {ff_cart_item_quantity}</p>
    <p>Subtotal: {ff_cart_item_subtotal}</p>
    {ff_cart_item_remove}
  </div>
[/ff_cart_items]
```

When cart is empty, falls back to `{ff_cart_items_table}` (shows empty cart message with shop link).

### 5.2 `[ff_customer_orders]`

```
[ff_customer_orders]
  <div class="order-row">
    <span>{ff_order_date}</span>
    <span>{ff_order_invoice_no}</span>
    <span>{ff_order_total}</span>
    <span>{ff_order_status}</span>
  </div>
[/ff_customer_orders]
```

---

## 6. Live AJAX Cart Updates

### Problem
Cart tokens render static HTML at page load. When the user changes quantity via +/- buttons (powered by FluentCart's own JS AJAX), price, subtotal, total, and count values don't update — user must refresh the page.

### Solution Architecture

**PHP AJAX Endpoint** (`wp_ajax_ffbb_cart_data` / `wp_ajax_nopriv_ffbb_cart_data`):
- Hooked in `FluentFlow\Core\Plugin::register_hooks()`
- Handler: `FluentFlow\Data\DataFetcher::handle_ajax_cart_data()`
- Returns JSON: `{ items: [{ id, quantity, price_formatted, subtotal_formatted }], total, subtotal, item_count }`

**Frontend JS** (`assets/js/ffbb-cart-live.js`):
- Listens for `fluentCartFragmentsReplaced` and `fluentCartNotifyCartDrawerItemChanged` events (dispatched by FluentCart after any cart AJAX operation)
- Fetches cart data from the endpoint
- Updates DOM elements matching `[data-ffbb-token="price"][data-ffbb-item-id="X"]`, etc.
- Updates quantity input values directly
- Adds `.ffbb-item-stale` class to removed items (style with `display:none`)

**Enqueue Logic**:
- `FluentFlow\Data\DataFetcher::enqueue_cart_live_assets()` — called by every cart token resolver
- Uses `static $enqueued` flag to prevent duplicate enqueue
- Localizes `ffbb_cart_vars.ajaxurl` for the JS

**Data Attributes on Token Outputs:**

| Token | `data-ffbb-token` | `data-ffbb-item-id` | Update Method |
|---|---|---|---|
| `{ff_cart_item_price}` | `price` | ✅ | `textContent` |
| `{ff_cart_item_subtotal}` | `subtotal` | ✅ | `textContent` |
| `{ff_cart_item_quantity}` | `quantity` | ✅ | Input `.value` |
| `{ff_cart_total}` | `cart_total` | ❌ | `textContent` |
| `{ff_cart_subtotal}` | `cart_subtotal` | ❌ | `textContent` |
| `{ff_cart_item_count}` | `cart_item_count` | ❌ | `textContent` |

---

## 7. FluentCart Internals We Discovered

### 7.1 Cart Data Structure

Cart items are stored as a JSON array in the `cart_data` column of the `fct_carts` table. Each item has these keys (set by `CartHelper::generateCartItemFromVariation()`):

```php
$cartItem = Arr::only($data, [
    'id',                  // Variation ID (from fct_product_variations)
    'object_id',           // Same variation ID
    'post_id',             // Product post ID (from wp_posts)
    'quantity',            // Current quantity
    'post_title',          // Product title
    'title',               // Variation title
    'price',               // Unit price in cents
    'unit_price',          // Same
    'coupon_discount',     // Coupon discount in cents
    'fulfillment_type',    // 'physical' or 'digital'
    'featured_media',      // Image URL (variation thumb → product thumb → placeholder SVG)
    'other_info',          // Payment type, promo info, etc.
    'cost',                // Item cost
    'view_url',            // Product permalink with variation param
    'line_total_formatted', // Pre-formatted total
    'line_total',          // Total in cents
    'subtotal',            // Same as line_total
    'total',               // Total after discounts
    'variation_type',      // 'simple', 'variable', etc.
    'is_custom',           // Boolean
]);
```

### 7.2 Cart Model

- **Class:** `FluentCart\App\Models\Cart`
- **Key method:** `CartHelper::getCart()` — resolves the current cart by checking cookie hash, then user ID, returns null if neither
- **Add item:** `addByVariation(ProductVariation, $config)` — creates cart item data via `CartHelper::generateCartItemFromVariation()`, then stores via `addItem()`
- **cart_data accessor:** `getCartDataAttribute()` decodes JSON, applies `Helper::loadBundleChild()`, caches per request
- **cart_data mutator:** `setCartDataAttribute()` JSON-encodes the array

### 7.3 Customer Model

- **Class:** `FluentCart\App\Models\Customer`
- **Key resource:** `CustomerResource::getCurrentCustomer()` — tries user_id first, then email match, caches per request
- **Relationships:**
  - `$customer->orders()` — HasMany through `Order` model
  - `$customer->billing_address` — HasMany through address relation
  - `$customer->shipping_address` — HasMany through address relation
- **Properties:** `first_name`, `last_name`, `email`, `photo`, `ltv`, `purchase_count`, `first_purchase_date`, `last_purchase_date`, `aov`

### 7.4 Order Model

- **Class:** `FluentCart\App\Models\Order`
- **Properties available for tokens:** `id`, `total_amount`, `subtotal`, `status`, `payment_status`, `payment_method_title`, `currency`, `receipt_number`, `invoice_no`, `uuid`, `type`, `created_at`
- **Status labels:** draft, pending, on-hold, processing, completed, failed, refunded, partial-refund, cancelled
- **Type labels:** payment (One-time), subscription (Subscription), renewal (Renewal)

### 7.5 Product & Variation Models

- **Product:** `FluentCart\App\Models\Product` — extends WP post, has `detail` (ProductDetail), `variants` (ProductVariation)
- **ProductVariation:** `FluentCart\App\Models\ProductVariation` — linked to product via `post_id`
- **ProductDetail:** `FluentCart\App\Models\ProductDetail` — linked to product via `post_id`, has `featured_media`, `galleryImage`, `min_price`, `max_price`, `stock_availability`, `fulfillment_type`, `variation_type`
- **Thumbnail fallback chain (variation):**
  1. `$variation->thumbnail` → reads `fct_product_meta` where `meta_key = 'product_thumbnail'`, returns first image URL
  2. `$variation->product->thumbnail` → reads `ProductDetail::featured_media` which reads `fct_product_meta` where `meta_key = 'product_gallery_images'`, returns first image URL
  3. If neither: returns `Vite::getAssetUrl('images/placeholder.svg')`

### 7.6 Product Meta Table

- **Table:** `fct_product_meta`
- **Columns:** `id`, `object_id`, `object_type`, `meta_key`, `meta_value`
- **Key meta_keys:** `product_thumbnail` (variation thumbnails), `product_gallery_images` (product gallery)

### 7.7 FluentCart AJAX System

- All cart operations go through: `admin-ajax.php?action=fluent_cart_checkout_routes&fc_checkout_action={action}`
- Key actions: `fluent_cart_cart_status` (get cart state), `fluent_cart_cart_update` (update quantity)
- Server returns HTML **fragments** (selector + content pairs) for cart drawer, checkout area, badge count
- Fragments are applied to DOM by `payment-loader.js` → `handleFragments()`
- After fragment replacement, these events fire:
  - `fluentCartFragmentsReplaced` (on window)
  - `fluentCartNotifySummaryViewUpdated` (on window)
  - `fluentCartNotifyCartDrawerItemChanged` (on window, from FluentCartApp.js)
  - `fluentCartCheckoutDataChanged` (on window, after checkout summary fetch)
- Fragment filter hook (PHP): `fluent_cart/checkout/after_patch_checkout_data_fragments`

### 7.8 Currency & Formatting

- Prices stored in **cents** (integers). Divide by 100 for display.
- Formatter: `Helper::toDecimal($cents)` or manual `/ 100`
- Currency symbol: not directly exposed by models; Fetcher uses `apply_filters('ffbb_currency_symbol', '$')` overrideable via filter

### 7.9 Key PHP Classes Used

```php
FluentCart\App\Helpers\CartHelper               // Cart resolution
FluentCart\App\Models\Cart                       // Cart model
FluentCart\App\Models\Customer                   // Customer model
FluentCart\App\Models\Order                      // Order model
FluentCart\App\Models\Product                    // Product model
FluentCart\App\Models\ProductVariation           // Variation model
FluentCart\App\Models\ProductDetail              // Product detail model
FluentCart\App\Models\Subscription               // Subscription model
FluentCart\Api\Resource\CustomerResource          // Customer resolution
FluentCart\App\Vite                              // Asset URL helper
FluentCart\App\Modules\Templating\AssetLoader    // Cart/checkout asset enqueue
```

### 7.10 Bricks Integration Details

- **Filter:** `bricks/dynamic_tags_list` — registers all tokens as Bricks dynamic tags (underscore, not slash)
- **Filter:** `bricks/dynamic_data/render_tag` — resolves individual `{ff_*}` tags
- **Filter:** `bricks/dynamic_data/render_content` — bulk replacement of `{ff_*}` in content
- **Filter:** `bricks/frontend/render_data` — same as above, different context
- **Reference implementation:** `bricks/includes/woocommerce.php` (lines 1941-1977) for `wooCart` query type
- **Query loop hooks:** `bricks/setup/control_options`, `bricks/query/run`, `bricks/query/loop_object`, `bricks/query/loop_object_id`, `bricks/query/loop_object_type`

### 7.11 Admin Dashboard & Licensing

- **Class:** `FluentFlow\Admin\AdminDashboard`
- **Settings stored in:** `ffbb_settings` option (serialized array)
- **Key settings:** `modules` (array of enabled/disabled module IDs), `license` (license key string)
- **Dashboard renders at:** `admin.php?page=fluentflow`
- **License verification URL:** `https://fluentflow.io`
- **Deactivation link filter:** `plugin_action_links_{basename}`

---

## 8. Complete Build History

### Session 1 — Initial Cart Token Fix

**Problem:** `{ff_cart_item_name}`, `{ff_cart_item_image}`, etc. returned empty on cart page.

**Root cause:** `get_cart_model()` was using a broken manual lookup that queried `Customer::where('user_id', $userId)->first()` then tried to access the customer's cart through a relationship. FluentCart stores carts keyed by a cookie hash, not by user ID directly.

**Fix:**
- Replaced `get_cart_model()` with `CartHelper::getCart()` — FluentCart's own resolution chain: cookie hash → user ID → null
- Replaced `get_customer_model()` with `CustomerResource::getCurrentCustomer()` — queries by user_id OR email, per-request caching

### Session 2 — Cart Items Table Token

Added `{ff_cart_items_table}` — renders a full cart HTML table with:
- Product column (image + title + variation)
- Price column
- Quantity with +/- buttons (FluentCart data attributes)
- Subtotal column
- Remove button (FluentCart data attributes)

### Session 3 — Cart Items Query Loop & Individual Tokens

**Problem:** Monolithic table can't be individually styled per field in Bricks.

**Solution:** Built a proper Bricks query loop system:
- Registered `ff_cart_items` as a custom Bricks query type
- Created individual tokens: `{ff_cart_item_name}`, `{ff_cart_item_image}`, `{ff_cart_item_price}`, `{ff_cart_item_quantity}`, `{ff_cart_item_subtotal}`, `{ff_cart_item_remove}`, `{ff_cart_item_url}`, `{ff_cart_item_variation}`, `{ff_cart_item_id}`
- Added `[ff_cart_items]` container shortcode for non-Bricks
- Created cart item context system (`$cart_item_context` static property, `set_cart_item_context()`, `get_current_cart_item()`)
- Merged cart + cart item tokens under single "Cart" group

### Session 4 — Checkout Form Token

Added `{ff_checkout_form}` token that delegates to `do_shortcode('[fluent_cart_checkout]')` via output buffering.

### Session 5 — Customer Orders Query Loop

Added full customer profile support:
- Order context system (`$order_context`, `set_order_context()`, `reset_order_context()`, `get_current_order()`)
- `get_order_model()` updated to check order context (static → Bricks loop → `$GLOBALS['fc_order']` → direct ID lookup)
- `ff_customer_orders` Bricks query type (5 filters)
- `[ff_customer_orders]` container shortcode
- New tokens: `{ff_order_invoice_no}`, `{ff_order_uuid}`, `{ff_order_type}`
- New customer tokens: `{ff_customer_photo}`, `{ff_customer_billing}`, `{ff_customer_shipping}`
- All registered in token registry + shortcodes

**Fixes during this session:**
- Button `data-cart-id` attribute (was `data-process-id`)
- Enqueue FluentCart JS properly for buttons
- Shortcode + dynamic tag resolution both use `FluentFlow\Data\DataFetcher::resolve()` consistently
- Removed admin email from license activation

### Session 6 — Live AJAX Cart Updates

**Problem:** Cart tokens rendered static HTML that didn't update when user changed quantities via AJAX.

**Solution:**
- Added `data-ffbb-token` and `data-ffbb-item-id` attributes to all cart token outputs
- Created `ffbb-cart-live.js` — listens for FluentCart's `fluentCartFragmentsReplaced` event
- Created PHP endpoint `wp_ajax_ffbb_cart_data` returning cart JSON
- JS fetches cart data on event, updates DOM: price/subtotal `textContent`, quantity input `.value`, cart total/subtotal/count
- Stale items (removed) get `.ffbb-item-stale` class
- Added `enqueue_cart_live_assets()` with static dedup flag

### Session 7 — Cart Item Image Fix

**Problem:** `{ff_cart_item_image}` returned empty when `featured_media` was missing from cart data.

**Fix:** Added fallback chain:
1. `$item['featured_media']` (cart data)
2. `ProductVariation::find(object_id)->thumbnail` (FluentCart variation meta)
3. `$variation->product->thumbnail` (FluentCart product gallery)
4. `get_the_post_thumbnail_url($post_id)` (WordPress featured image)
5. `Vite::getAssetUrl('images/placeholder.svg')` (FluentCart placeholder)

---

## Summary Statistics

| Category | Count |
|---|---|
| Total tokens | ~52 |
| Token groups | 8 (Product, Customer, Order, Cart, Subscription, Coupon, Checkout, —) |
| PHP files in `src/` | 14 |
| PHP files in `includes/` | 5 |
| Module files | 4 |
| Bricks query types | 5 (Cart Items, Customer Orders, Customer, Subscriptions, Coupons) |
| Container shortcodes | 2 (`[ff_cart_items]`, `[ff_customer_orders]`) |
| AJAX endpoints | 1 (`ffbb_cart_data`) |
| Frontend JS files | 1 (`ffbb-cart-live.js`) |
| WordPress filters used | 17 (Bricks: dynamic tag + query loop hooks) |
| WordPress actions used | 4 (init, admin_enqueue_scripts, wp_ajax_*, activation/deactivation) |
| FluentCart model classes used | 6 |
| FluentCart helper classes used | 3 |
| FluentCart JS events listened to | 2 (`fluentCartFragmentsReplaced`, `fluentCartNotifyCartDrawerItemChanged`) |
| PHP version required | 8.0+ (uses `match`, `str_starts_with`, named args in `shortcode_atts`) |
