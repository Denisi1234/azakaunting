# Tanzania POS Competitive Feature Gap Audit

**Reviewed:** 29 October 2026  
**Product reviewed:** the current Open Source Point of Sale working tree  
**Comparison set:** public feature pages from Tanzania-focused retail and pharmacy POS vendors. Vendor pages are marketing claims, not independent product verification.

## Implementation progress

The current working tree now includes these foundations from the roadmap:

- **Named local tenders:** configurable payment methods, split payments, reference capture, tender reporting, and cashup totals. These record payment information; they do not collect or verify provider payments.
- **Cashier shifts:** opening float, POS sale attribution by cashier and location, reasoned cash in/out movements, closing count, expected cash, variance, and recent shift history. Closing a shift ends the cashier's ability to complete further POS sales until another shift is opened.
- **Daily statement reconciliation:** users with the existing payment-report permission can compare recorded POS tender totals with amounts collected from a bank or provider statement, save a reference/note, and retain the reconciling employee and time. Expected amounts update from the current sales data.
- **Price and discount approval:** staff without the existing price-change grant can submit a reasoned request for an item-price/line-total override or a larger discount. A permitted manager approves or rejects it with a decision reason; the exact approved change is applied in the requesting session and linked to the eventual sale for review.
- **Customer credit controls:** customer profiles support optional credit limits and payment terms, show calculated outstanding balances and invoice-level due dates/aging, and reject a credit sale or invoice that would exceed the configured limit. The final check runs inside the sale transaction and locks the customer row on MySQL to prevent simultaneous checkouts from both using the same remaining limit.
- **Purchase orders:** a receiving cart can be saved as a draft order, a different employee with employee-management access approves or cancels it with a recorded reason, and approved orders can be received in partial deliveries. Each receipt is linked in the stock receiving transaction, checks remaining quantities and location/supplier, requires a note for short deliveries, and uses an idempotency token to guard against duplicate submission.

These are source changes and syntax-checked in this working tree. They have not been verified against a migrated database or exercised in a running browser. Provider APIs/imports, offline queue/synchronization, full customer payment statements/reminders, manager approval for refunds/voids and cash expenses, supplier bill/payable management, in-transit stock transfers, all-shift manager reporting, and the other roadmap gaps below remain separate work.

## Executive assessment

The product has a capable self-hosted retail POS core: checkout, receipts, barcode workflows, stock and receiving, reorder levels, multi-location permissions, employee grants, cashups, customer records, rewards, taxes, and sales/inventory reports. The strongest gaps against current Tanzania POS offers are TRA fiscal receipt integration, reliable offline sales and synchronization, provider-specific mobile-money reconciliation, and branch-wide operating workflows. Pharmacy-ready batch/expiry controls and a complete accounting ledger are also absent or incomplete.

This codebase is not yet a like-for-like hosted POS service. It is a browser application that an operator deploys and maintains. Product competitiveness therefore depends on deployment, backups, support, onboarding, and operating reliability as well as source features.

## Existing capability and gap matrix

| Area | What the code currently supports | Gap versus Tanzania-focused offers | Priority |
|---|---|---|---|
| Checkout and receipts | Barcode/search checkout, split payment rows, receipt printing/email, credit/due and a generic Wallet tender | No provider-level M-Pesa, Mixx by Yas, Airtel Money or HaloPesa connection or settlement/reference reconciliation found. A generic tender records a method; it does not collect or verify a payment. | P0 |
| TRA fiscal receipts | Configurable tax rates, tax categories/jurisdictions and ordinary POS receipts | No EFDMS/VFD fiscalization, signed TRA request, fiscal control number/QR receipt, or fiscal error/retry handling found. Tax calculation and a tax jurisdiction label do not make a receipt TRA-fiscalized. | P0 |
| Offline operation | Browser application; the current source includes responsive layout changes | No service worker, local sale queue, conflict handling, or synchronization workflow found. A responsive screen still depends on the server connection. | P0 |
| Stock control | Sale/receiving inventory updates, per-location quantities, reorder levels, low-stock report, item kits, serialized-item support, and location requisitions/transfers | No batch/lot expiry tracking or FEFO selling flow found. No automated low-stock notification was found. Transfer lifecycle should be checked in hands-on use for dispatch, receipt, discrepancy, and audit coverage. | P1; P0 for pharmacies |
| Profit and analytics | Summary reports for sales by date, items, categories, customers, employees, and expenses; cost and profit fields are present | Existing reports provide item/date analysis. A location-profit comparison was added in this working tree, but route/runtime behavior could not be checked because application bootstrap currently fails. More complete owner dashboards, period comparisons, peak-hour analysis, and exportable branch KPIs are not established. | P1 |
| Branches | Multiple stock locations, location-scoped grants, per-location stock, and requisition/receiving flows in one installation | No tenant/organization cloud layer or managed cross-branch synchronization was found. Branch analytics and day-to-day branch switching are limited compared with hosted multi-branch products. | P1 |
| Staff controls | Employee logins, grants by module/subpermission/location, sale employee attribution, and server-side `sales_change_price` permission enforcement. This tree adds price/discount approvals and separate-employee purchase-order approval/cancellation with reasons. | Approval workflows for voids, refunds, and expenses remain open. | P1 |
| Till and shift control | Cashups record opening/closing amounts, employee IDs, and tender totals | No enforced cashier till session lifecycle with a blind count, expected-versus-counted variance, handover, and manager sign-off was established. | P1 |
| Customer credit | Customer records, due/credit tenders, and rewards points. This working tree now adds optional limits and terms, calculated open balances, invoice-level aging, and a checkout limit check. | Full transaction statements, collection history/workflow, and automated overdue reminders remain unbuilt. | P1 |
| Procurement | Supplier receiving/payment records. This tree adds draft and approved purchase orders, partial receipt linkage, short-delivery notes, and received-quantity status. | Supplier invoice/bill matching, supplier repayment allocation, and payable aging remain absent. | P1 |
| Accounting | Existing sales/expense reports; an Accounting controller and views are present in the current uncommitted working tree | The current Accounting pages are summary calculations over POS tables. They are not evidence of a complete double-entry general ledger, journal controls, chart of accounts, period close, or reconciled bank ledger. Review the custom calculations before relying on them for statutory accounts. | P1 |
| Localization | Swahili (Tanzania) is an available locale, alongside configurable currency/date/time and tax settings | Locale availability does not prove translation completeness, consistent Tanzanian terminology, or translated support/onboarding. Translation coverage and checkout strings need a focused audit. | P1 |
| Mobile | Browser access and responsive work in the shared portal; the repo contains a device audit noting route-level visual QA remains outstanding | No native mobile app, offline mobile selling, or verified camera scanning workflow found. The static responsive pass still needs actual device checks across checkout, receiving, reports, and dialogs. | P1 |
| Owner notifications | SMS composition/integration support exists in the application | No scheduled end-of-day owner summary, automated low-stock/overdue-credit alerts, or notification preferences were found. | P2 |
| Integrations and sales channels | Mailchimp and SMS-related integration/configuration code exists | No supported webhooks/API for external order intake or automatic marketplace/e-commerce order synchronization was established. | P2 |
| Service delivery | Self-hosted web application with configurable settings and themes | No built-in subscriptions, tenant provisioning, hosted backups/restore service, local support operation, SLA, or guided onboarding in the repository. These require a product/service layer outside the POS screens. | P1 for a commercial hosted offer |

## Suggested build order

### P0 — Make the till dependable and Tanzania-ready

1. **Mobile-money reconciliation:** support named local tenders and reference numbers, then add provider statement import or API reconciliation. Keep manual recording distinct from real payment collection.
2. **Offline resilience:** define a supported offline device model first. Add durable local transactions, idempotent sync, conflict/error queues, stock reservation rules, and clear sync state. This is a major architecture feature, not a CSS change.
3. **Reliability basics:** document supported deployment, automated backup/restore, upgrade/rollback, monitoring, and data export. A shop must know sales survive device or server failure. TRA fiscal receipts remain out of scope as requested.

### P1 — Close the control and multi-branch gaps

5. **Approval workflows:** introduce explicit request/approve/reject states for high-risk actions and retain approver, requester, timestamp, reason, before/after values, and immutable history. Reuse existing permission conventions and familiar screens.
6. **Till sessions:** bind each cashier to a till session; record opening float, expected tender totals, blind close count, variance, handover, and authorized corrections.
7. **Branch comparison:** make location-profit comparison a first-class report with date range, revenue, cost, gross profit, expenses where reliably attributable, and margin. Verify the new report in the running app and ensure every query is restricted to the user's permitted locations.
8. **Purchase lifecycle and transfers:** purchase orders now support draft, approval, linked partial receipts, and discrepancy notes. Stock transfers now use request, independent approval, source dispatch, in-transit balances, partial destination receipt, and documented short receipt. Supplier bills/payables and audited return/disposal workflows remain open. Keep stock movement immutable and attributable.
9. **Credit collection:** add credit limits, aging, repayment history, due dates, and opt-in SMS/WhatsApp reminders.
10. **Accounting foundation:** decide whether to integrate a ledger or implement a proper double-entry system before positioning the summary pages as accounting. Reconcile sales, taxes, inventory, payments, expenses, and opening balances.
11. **Pharmacy edition (if targeted):** batch/lot-level stock, expiry alerts, FEFO, supplier batch capture, returns/recalls, and any required pharmacy/insurance workflow. Avoid claiming pharmacy readiness without the regulatory and operational review.
12. **Mobile QA:** verify the existing UI at 320, 360, 390, and 768 px on real browsers, especially checkout, receiving, dialogs, tables, printer flow, and barcode input. Keep the existing UI/CSS approach unless evidence shows a specific component must change.

### P2 — Build retention and reach

13. Scheduled daily owner summaries and alert settings.
14. Stable API/webhooks for e-commerce and external accounting integrations.
15. Complete and review Swahili (Tanzania) translations and local help/onboarding.
16. If selling as a hosted service, add tenant isolation, provisioning, plans, billing, data export, restore tooling, support operations, and clear uptime/data-retention terms.

## Evidence from this codebase

- Core scope: `README.md` lists stock, tax, sales, expenses, receipts, barcodes, customer/supplier records, multi-user permissions, reports, receiving, gift cards, rewards, restaurant tables, and multilanguage.
- Tender types: `app/Helpers/locale_helper.php` and `app/Models/Sale.php` show application-level payment choices; these are not payment-provider adapters.
- Price permission: `app/Controllers/Sales.php` enforces the `sales_change_price` grant server-side.
- Low stock: `app/Models/Reports/Inventory_low.php` compares location quantity to item reorder level.
- Locations and receiving: `app/Models/Stock_location.php` and `app/Controllers/Receivings.php` implement location-scoped access. Requisition mode now creates staged transfers through `app/Models/StockTransfer.php` and `app/Controllers/StockTransfers.php`; dispatch removes source stock and receipt adds destination stock, with the balance shown in transit. Transfer design follows ERPNext's separate outward and inward stock entries for goods in transit ([ERPNext](https://docs.frappe.io/erpnext/how-to-manage-material-transfers-and-goods-in-transit)).
- Reports: `app/Models/Reports/Summary_report.php`, `Summary_items.php`, and `Summary_sales.php` calculate sales, cost, and profit aggregates.
- Mobile status: `docs/mobile-responsiveness-audit.md` documents shared responsive changes and states that route-by-route visual verification remains outstanding.
- Custom financial pages exist in `app/Controllers/Accounting.php` and `app/Views/accounting/`; their presence alone does not establish a general ledger.

## External market evidence

Public vendor pages reviewed on 29 October 2026 include:

- [DukaPOS](https://dukapos.co.tz/en) markets offline mobile sales and synchronization, local mobile-money tender names, branch comparison, credit tracking, Swahili support, and daily SMS summaries.
- [SwiftPOS](https://swiftpos.co.tz/) markets mobile apps, multi-branch reporting, team controls, and offline-friendly checkout.
- [PowerSales](https://powersales.co.tz/) markets built-in VFD fiscalization, branch stock, local setup, training, and support.
- [eSecured POS documentation](https://docs.erp.co.tz/docs/modules/pos) describes local tenders and TRA fiscalization within an ERP product.
- [MoPOS](https://www.mopos.co.tz/) advertises discount requests, while [Trakora's retail help](https://trakora.co.tz/help/retail) describes manager authorization for discounts, returns, and voids. These are vendor claims; OSPOS now starts with an audited price/discount request flow, while other approval actions remain open gaps.
- [DukaPOS wholesale POS](https://dukapos.co.tz/en/wholesale-pos-system) promotes supplier purchase orders, receiving against orders, credit terms, and customer/supplier statements. [ERPNext Purchase Receipt documentation](https://docs.frappe.io/erpnext/purchase-receipt) separates purchase commitment, accepted/rejected receipt, supplier invoice/payable, and payment, and supports multiple partial receipts against one order. This tree now supports draft/approval and linked partial purchase receipts. [ERPNext goods-in-transit guidance](https://docs.frappe.io/erpnext/how-to-manage-material-transfers-and-goods-in-transit) separates dispatch from destination receipt; the OSPOS staged transfer now uses those states. The supplier invoice/payable lifecycle remains to build.
- [Apilox](https://www.apilox.co.tz/apilox) and [PharMax](https://www.pharmax.co.tz/) show the additional expectations in the pharmacy segment: offline operation, expiry/batch handling, insurance, and approval workflows.
- [TRA EFD Receipt API](https://virtual.tra.go.tz/efdmsRctApi/) is the official receipt API reference. Integration requirements and authorization must be confirmed with TRA or an authorized fiscal-device vendor.

These pages describe how vendors position their products. They do not independently confirm uptime, regulatory approval, or actual feature quality.

## Named competitor review: tHL Accounting

The supplied `thlaccountingv2/interface/index.php#home` URL was not publicly accessible to the review tool, so no authenticated business dashboard or customer data was accessed. The comparison below uses tHL's public website, FAQ, and user manual. The manual appears older than the current feature announcements, so verify the current live product before treating every manual section as current.

### Publicly advertised strengths

- **Hosted subscription offer:** tHL advertises a 14-day trial, online support, mobile/browser access, and tiered TZS monthly plans. Its public page lists plans at TZS 10,000, 20,000, 40,000, and 60,000 monthly, with 2/3/4/6 users and 1/2/3/5 branches respectively. Prices and plan limits can change.
- **Accounting depth:** the user manual describes chart of accounts, journals, expenses posted to accounts, IFRS groups, budgets, and accounting reports. tHL therefore appears materially ahead of the current OSPOS working tree's custom P&L/balance-sheet summary pages in accounting workflow breadth.
- **Sales and procurement:** its manual describes cash sales, invoices, payments, recurring invoices, customer credit, returns/refunds, purchase orders, and bill payments. OSPOS has POS, invoice/quote/work-order flows, receiving, returns, and due tenders, but the reviewed source does not show equivalent recurring billing or full PO-to-bill workflow.
- **Commercial workflows:** tHL advertises online product sales, WhatsApp sharing of orders/invoices/receipts, contract and lease management, project budgets, accounting-period closing, automatic stock taking, and biometric attendance integration. These were not established in the OSPOS source reviewed.
- **Company and branch access:** the manual describes multiple companies, branches, and user visibility levels for personal, branch, or all-company data. OSPOS provides location-scoped grants in one installation, but does not provide tHL's hosted account/package administration layer in this repository.
- **Payroll/HR:** current public package pages advertise payroll limits per plan and payroll/HR at Enterprise. OSPOS's current working tree includes employee HR fields and timeclock capture, but not a full payroll engine.

### Publicly stated limitations and evidence gaps

- tHL's FAQ says it supports only one currency at a time and does not provide stock reconciliation. It describes FIFO and says items nearing expiry are sold first, but that FAQ does not establish lot-level traceability or a pharmacy-grade recall process.
- The public pages reviewed did not establish direct M-Pesa/Airtel Money/Mixx/HaloPesa collection or automated reconciliation, TRA EFDMS/VFD fiscalization, or offline retail POS synchronization. Absence of a public claim is not proof the deployed product lacks it.
- tHL's current marketing page says it has an application, while an FAQ answer says an application was still being developed. A Google Play listing exists and reports an August 2026 update, so the FAQ appears stale on this point.
- The public website advertises automatic stock taking, while the FAQ says it does not provide stock reconciliation. Treat the difference as unresolved until the workflow is demonstrated.

### What this means for our positioning

tHL should be treated as a broader small-business accounting suite with a POS feature, not only as a POS rival. Its clearest competitive advantage is the surrounding business system: ledger workflows, budgets, period locks, billing/procurement, payroll tiers, WhatsApp sales documents, hosted onboarding, and packaged multi-branch access. OSPOS can compete on its established retail POS/inventory core, configurable extensions, restaurant workflows, rewards/gift cards, and deployment flexibility, but would need either a true accounting layer or an accounting integration to match the full-suite promise. For a Tanzania-first retail offer, TRA fiscalization and dependable offline operation remain high-risk gaps for both product differentiation and operational readiness until verified.

Sources: [tHL public site and package claims](https://www.thlaccounting.com/), [tHL FAQ](https://www.thlaccounting.com/faqs.php), [tHL user manual PDF](https://thlaccounting.com/tHL%20Accounting%20User%20Manual.pdf), and [tHL Android app listing](https://play.google.com/store/apps/details?id=twiga.com.thlaccounting).
