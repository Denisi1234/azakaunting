# Mobile Responsiveness Audit

## Scope and review method

The source review covers the shared portal shell and all 93 non-partial PHP page templates under `app/Views`. This includes 58 templates with forms and 32 with tables. Error templates and shared partials are excluded from those counts. Page templates are grouped by workflow below so visual review can follow the way staff use the portal.

The intended layout is a persistent sidebar at widths of 992 CSS pixels and wider, and a hamburger drawer below 992 pixels. The drawer must sit above its backdrop, support touch and keyboard input, and close after a module is selected. Responsive behavior is shared where possible, with focused rules for the point of sale, charts, data tables, settings tabs, and print-oriented documents.

## Page-section inventory

| Section | Templates | Main mobile risks to check |
| --- | ---: | --- |
| Sales and point of sale | 16 | Register cart columns, checkout controls, filters, suspended sales, modal forms, receipts, invoices, quotes, and work orders |
| Reports | 12 | Dense filters, chart labels, export controls, long result tables, sticky headers, and report summaries |
| Configuration | 18 | Long settings forms, horizontal tab navigation, toggle controls, and tooltips |
| Items and inventory | 6 | Search/filter toolbars, bulk edit, CSV import, count details, and wide item tables |
| Taxes | 6 | Multi-tab navigation, rate tables, and nested configuration forms |
| Customer, people, employee, supplier | 7 | Long labels, contact forms, employee HR fields, and management tables |
| Expenses, expense categories, gift cards, cashups | 8 | Date and amount controls, table actions, and narrow form rows |
| Accounting and timeclocks | 3 | Custom financial tables, reconciliation controls, and date/time entry |
| Home and office | 3 | Dashboard metrics, charts, and module tiles |
| Receiving | 3 | Receiving cart, payment controls, and receipt output |
| Attributes and item kits | 5 | Nested item forms and data tables |
| Barcodes and messages | 3 | Barcode sheet print layout and SMS composition |
| Login, access denied, data view | 3 | Login panel sizing, error content, and diagnostic tables |

## Findings and changes

### Shared shell and navigation

- Desktop sidebar remains visible by default at 992 pixels and wider. Smaller layouts use the hamburger drawer.
- The drawer is layered above the backdrop so the menu receives taps; the trigger is hidden while the drawer is open so it does not cover the drawer header.
- The responsive shell uses a fixed-height wrapper with an independently scrolling content pane. Device checks should include browser chrome changes, landscape rotation, and the on-screen keyboard.

### Dashboard

- The original four KPI columns were too narrow at phone widths. The mobile stylesheet now switches them to two columns and reduces value sizing while allowing the growth details to wrap.
- Chart heading controls and legends wrap on narrow screens.

### Tables and management pages

- The source review found 32 table views. Shared `#table_holder` layouts now permit horizontal scrolling inside the table area, keeping the page itself within the device width.
- The Bootstrap Table mobile extension is loaded but defaults to disabled. Do not turn on card view for every table without checking column meaning and action behavior; select appropriate tables during visual review.
- Sticky headers, pagination, filters, and column visibility still need real-device checks at phone and tablet widths.

### Forms, toolbars, and settings

- Mobile title bars now wrap actions into usable rows. Toolbars can wrap filters and buttons rather than forcing a single long line.
- Small-screen form labels can wrap, and controls are constrained to their available width.
- Configuration tabs can scroll horizontally rather than widening the whole page. Verify that the selected tab remains obvious and reachable by touch.
- Date pickers, select pickers, autocomplete menus, validation messages, and modal forms need route-level checks because their popovers may be positioned by JavaScript.

### Sales register

- The cart table already has an intentional minimum width for its multiple editable columns. It is now inside the existing horizontal-scroll wrapper so scrolling stays local to the cart.
- The duplicate `register` ID on the payment table has been replaced with `payment_table` so scripts and mobile styles address the cart table consistently.
- Register checkout remains a high-priority workflow for hands-on review: test add/edit/delete item, customer selection, payment, suspend/cancel, and finish actions on a phone-sized viewport.

### Print and special workflows

- Receipts, invoices, quotes, work orders, and barcode sheets have print-specific layouts. Review screen readability separately from printed output; avoid changing print dimensions to solve screen overflow.
- SMS, CSV import, barcode generation, accounting, and timeclock pages have fewer shared patterns and should be checked as distinct workflows.

## Device and acceptance matrix

| Width | Expected navigation | Acceptance checks |
| ---: | --- | --- |
| 320 px | Hamburger drawer | No page-level horizontal overflow; primary fields and actions remain usable |
| 360 px | Hamburger drawer | Dashboard cards, menu, forms, and toolbar controls fit without clipped text |
| 390 px | Hamburger drawer | Tables scroll within their own region; dialogs and pickers fit |
| 768 px | Hamburger drawer | Tablet layout uses available width without squeezed desktop columns |
| 991 px | Hamburger drawer | Drawer remains usable immediately before the desktop breakpoint |
| 992 px | Persistent sidebar | Main content resizes cleanly and sidebar remains visible |
| 1280 px | Persistent sidebar | Desktop layout remains unchanged and content does not become unnecessarily narrow |

For each route, review portrait and landscape, touch target size, keyboard focus, browser zoom, open dropdowns and modals, long translated labels, and horizontal overflow. Record the route, width, observed issue, severity, and screenshot before making page-specific changes.

## Audit status

- Source inventory and shared CSS review: complete.
- Shared mobile improvements and point-of-sale cart scrolling: implemented in the current working tree.
- Source-based mobile-first redesign backlog for the requested portal sections: complete.
- Route-by-route visual review at the device matrix above: outstanding. The static source review cannot confirm every popover, table column, translation, or workflow state at runtime.

## Mobile-first redesign backlog

The current mobile rules mainly resize, wrap, or horizontally scroll desktop controls. Those behaviors prevent some layout breakage, but they do not make the busiest workflows feel designed for a phone. The findings below come from PHP template and stylesheet review; they still need visual and interaction verification at phone widths.

| Portal page | Desktop pattern that remains on mobile | Mobile-first redesign needed | Priority |
| --- | --- | --- | --- |
| Dashboard | KPI grid and chart are resized versions of desktop cards | Keep the two-column KPI grid, but validate 320 px widths and long currency values; place chart controls on a clear full-width row and simplify chart labels/legend for narrow screens | P2 |
| Customers | Dense management table and broad toolbar | Keep the table and its selection/actions; hide the ID and email columns by default on phones while keeping names, phone, total, and edit/message actions visible; wrap toolbar tools into clear rows | P1 |
| Items | Many-column inventory table plus import, create, bulk, and filter actions | Keep the familiar table; show item number, name, category, price, quantity, and row actions; hide IDs, supplier/cost/tax/image and dynamic attribute columns by default, with the column chooser available to reveal them | P1 |
| Item Kits | Wide table and component-entry rows | Keep the table; hide ID, description, and cost columns on phones while retaining kit number, name, selling price, and edit action; edit components as stacked item rows | P2 |
| Suppliers | Shared people-style table and long contact forms | Keep the table and row actions; hide ID, agency/category, and email columns on phones while retaining company, contact name, and phone; group forms into single-column contact and address sections | P2 |
| Reports | Desktop report directory, filter forms, chart legends, and wide result tables | Group report choices into touch-friendly links; use a compact filter step; give charts mobile label/legend treatment; keep detailed tables in a contained scroller with key totals visible | P1 |
| Receivings | Register-style multi-column item cart and payment sections | Use stacked cart rows with inline quantity/price controls, a visible receiving total, and a persistent complete action; group payment fields into a focused section | P0 |
| Sales | Register cart table, dense checkout fields, and table-based sales history | Highest-priority workflow: use stacked cart rows, make customer/item search primary, keep total/pay visible, and put payment in a focused panel; show sales history as compact transaction cards | P0 |
| Gift Cards | Management table and standard form layout | Keep the table; hide the ID column while retaining holder, card number, balance, and edit action; use a single-column issue/edit form | P2 |
| Messages | Desktop-style message composition and recipient controls | Make recipient selection and message composition a short vertical flow; keep character/segment count beside the message and make send easy to reach above the keyboard | P2 |
| Taxes | Multiple desktop tabs and rate/jurisdiction tables | Keep every tax section reachable in a two-column tab grid; hide only secondary rate/jurisdiction columns on phones and preserve the table and edit action | P1 |
| Expenses | Dense transaction list, date/filter controls, and form fields | Keep the table; hide ID, tax code/amount, description, and employee columns by default while retaining date, supplier, amount, payment, category, and edit; make entry forms single-column | P1 |
| Cashups | Reconciliation data and action-heavy table layout | Keep the table; prioritize open date, opening amount, and closed total, with remaining reconciliation columns available from the column chooser; group form inputs vertically | P1 |
| Office | Desktop module tile collection | Use a consistent two-column app grid with a large icon, readable label, and comfortable touch target | P2 |

### Shared mobile interaction patterns

- Use one-column forms with labels above controls and short, named sections.
- Keep search visible on lists; wrap secondary filters and actions into clear rows without hiding them behind a new mobile-only control.
- Keep operational tables recognizable. Hide only lower-priority columns on phones, leave primary data and actions visible, and let staff reveal optional fields from the existing column chooser. Keep a local horizontal scroller for dense analytical tables.
- Give frequent actions touch targets of at least 44 px and visually separate destructive actions from primary actions.
- Use sticky bottom actions only when they do not cover content or the software keyboard; verify safe-area spacing and scroll behavior.
- Treat receipts, invoices, quotes, work orders, and barcode sheets as documents: improve screen reading while preserving print layout.

### Audit limits

This backlog identifies desktop structures that remain in mobile markup; it is not a completed device-by-device walkthrough. Date pickers, autocomplete, select menus, modal sizing, keyboard overlap, translated labels, and table behavior still require route-level visual checks. A wrapped toolbar or shared table scroller alone does not count as a mobile redesign.
