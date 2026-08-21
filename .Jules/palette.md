## 2026-08-17 - Dynamic Row Accessibility in Modals
**Learning:** In modal forms with dynamic table/list rows (such as BOM product linking), inline select and number inputs often lack explicit `<label>` tags. Adding explicit `aria-label` attributes to these controls ensures screen readers accurately announce the purpose of each field ("Produto", "Quantidade").
**Action:** When creating or editing dynamic row input fields without visible labels, always provide descriptive `aria-label` attributes for each control.
