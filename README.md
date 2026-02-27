# FlipDev_FixDepersonalize

A Magento 2 module that fixes a critical bug causing **logged-in customers to be
redirected back to the cart** when Hyva Checkout and Full Page Cache (FPC) are
both enabled.

---

## The Problem

When a logged-in customer clicks *Proceed to Checkout*, the following chain of
events occurs:

1. `Magento\Checkout\Controller\Index\Index` is dispatched.
2. The Hyva Checkout plugin on that controller returns a
   `ResultFactory::TYPE_FORWARD` result, internally forwarding the request to
   `hyva_checkout/index/index`.
3. This forward triggers a **second dispatch cycle**.
4. During the second cycle, Magento's `DepersonalizePlugin` runs and calls
   `clearStorage()` on the customer session — because at that point the layout
   XML has not yet been parsed, so the `cacheable="false"` declaration on the
   Hyva Checkout layout is unknown to `DepersonalizeChecker`.
5. `getCustomerId()` now returns `null` → `hasItems()` returns `false` →
   the customer is redirected to `checkout/cart`.

Guest checkout works fine because there is no customer session to depersonalize.  
Disabling FPC also resolves the symptom, which confirms the root cause.

### Why `cacheable="false"` does not help

The `cacheable="false"` attribute in layout XML is evaluated **after** the
controller dispatch completes. During the TYPE_FORWARD second dispatch cycle,
`DepersonalizePlugin` executes before the layout is loaded, so Magento cannot
know the page is non-cacheable and does not suppress depersonalization.

---

## The Fix

Two plugins are provided:

### `DepersonalizeCheckerPlugin`

Overrides `Magento\PageCache\Model\DepersonalizeChecker::checkIfDepersonalize()` and
returns `false` for the `checkout`, `hyva_checkout` and `customer` module
routes. This prevents the customer session from being cleared on those pages.

### `LastOrderedItemsPlugin`

Catches exceptions in
`Magento\Sales\CustomerData\LastOrderedItems::getSectionData()`.  
When a product that was part of a previous order has been deleted from the
catalog, this exception would otherwise bubble up during checkout initialisation
and cause an additional redirect or blank page.

---

## Requirements

| Dependency          | Version  |
|---------------------|----------|
| PHP                 | `^8.4`   |
| Magento Open Source | `^2.4.8` |
| Hyva Checkout       | `^1.3`   |

---

## Installation

Add the repository to your project's `composer.json`:

```json
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/sickdaflip/mage2-fixdepersonalize"
    }
]
```

Then install:

```bash
composer require sickdaflip/mage2-fixdepersonalize:dev-main
bin/magento module:enable FlipDev_FixDepersonalize
bin/magento setup:upgrade
bin/magento cache:flush
```

---

## Uninstallation

```bash
bin/magento module:disable FlipDev_FixDepersonalize
composer remove sickdaflip/mage2-fixdepersonalize
bin/magento setup:upgrade
bin/magento cache:flush
```

No database schema is created by this module, so no `setup:uninstall` step
is required.

---

## Upstream Bug Report

This module works around a confirmed bug in Hyva Checkout. A bug report has
been filed in the `#hyva-general` channel on [hyva-themes.slack.com](https://hyva-themes.slack.com).

**Suggested upstream fix:** Either avoid `TYPE_FORWARD` in the Hyva Checkout
controller plugin so no second dispatch cycle occurs, or propagate the
non-cacheable flag before any plugins in the forwarded dispatch execute.

---

## License

[MIT](LICENSE) © 2026 Philipp Breitsprecher
