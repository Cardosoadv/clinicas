## 2025-08-21 - Custom Role Button Keyboard Support
**Learning:** Elements styled as buttons with `role="button"` and `tabIndex={0}` must handle both `Enter` and `Space` (`' '`) keys with `event.preventDefault()` to allow keyboard users to activate them without causing page scroll.
**Action:** When adding keyboard interactions to non-button elements with `role="button"`, handle both `Enter` and `' '` key events.
