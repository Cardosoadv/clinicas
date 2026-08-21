## 2025-05-18 - Topbar Search Dropdown ARIA Combobox & Keyboard Navigation
**Learning:** Fast global search components like `TopbarSearch` need complete ARIA combobox pattern attributes (`role="combobox"`, `aria-expanded`, `aria-controls`, `aria-activedescendant`) and keyboard handlers (`ArrowUp`/`ArrowDown`/`Enter`) so screen reader and keyboard-only users can navigate options without losing input focus.
**Action:** When creating or editing search dropdowns or autocomplete inputs, always implement active index tracking with `ArrowDown`/`ArrowUp` key handling and `role="listbox"` / `role="option"` markup.
## 2026-08-17 - Dynamic Row Accessibility in Modals
**Learning:** In modal forms with dynamic table/list rows (such as BOM product linking), inline select and number inputs often lack explicit `<label>` tags. Adding explicit `aria-label` attributes to these controls ensures screen readers accurately announce the purpose of each field ("Produto", "Quantidade").
**Action:** When creating or editing dynamic row input fields without visible labels, always provide descriptive `aria-label` attributes for each control.
