# Performance Validation Checklist (Lazy Video + Affiliate Deferral)

Use this guide to compare before/after performance when deploying the lazy video embeds, deferred LiveJasmin scripts, and slot machine lazy-load behavior.

## 1) Baseline (Before Change)
1. **Disable local caches** (browser cache + any WP caching plugin if possible).
2. **Capture metrics** from:
   - GTmetrix (fully loaded time, LCP, total requests).
   - Chrome Lighthouse (Performance score, LCP, TBT).
3. **Record page samples**:
   - Single model page with 10+ video embeds.
   - Video post page.
   - Homepage or models grid page.
4. **Save waterfall snapshots** for each sample.

## 2) After Change (Lazy Video + Affiliate Deferral)
Repeat the same steps and capture new metrics for the same pages.

## 3) Quick Verification Checklist
- [ ] Lazy placeholders render for video embeds (play button visible).
- [ ] Clicking placeholder loads video iframe and plays.
- [ ] `noscript` fallback renders iframe when JS disabled.
- [ ] LiveJasmin scripts load after:
  - [ ] 50% scroll,
  - [ ] any link click, or
  - [ ] 5 seconds idle.
- [ ] Slot machine assets load only when scrolled into view.
- [ ] No broken layouts or missing banners on model pages.

## 4) Performance Comparison Notes
| Metric | Before | After | Target |
| --- | --- | --- | --- |
| Fully loaded time (GTmetrix) |  |  | < 2s |
| LCP |  |  | < 2.5s |
| Total requests |  |  | Reduce |
| JS execution time |  |  | Reduce |

## 5) Waterfall Review (GTmetrix)
- Confirm video iframes do **not** request until user clicks.
- Confirm LiveJasmin scripts are absent on initial load.
- Confirm slot machine assets appear only after scroll into view.
