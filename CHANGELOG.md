# Changelog

All notable changes to `FlipDev_FixDepersonalize` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.0.0] — 2026-02-27

### Added
- `Plugin/DepersonalizeCheckerPlugin` — suppresses customer session depersonalization
  for `checkout`, `hyva_checkout` and `customer` module routes to fix the
  TYPE_FORWARD second dispatch cycle bug in Hyva Checkout + Magento FPC.
- `Plugin/LastOrderedItemsPlugin` — catches `NoSuchEntityException` in
  `LastOrderedItems::getSectionData()` when products from past orders have been
  deleted from the catalog.
- Full PSR-4 source layout under `src/`.
- MIT License.
- i18n placeholder structure (en_US, en_GB, de_DE, es_ES, fr_FR).
