---
name: Sociopro
description: Legacy Laravel social app design system for dense server-rendered community workflows.
colors:
  primary-purple: "#5a2ff9"
  primary-purple-deep: "#4929c2"
  primary-purple-soft: "#dfd9f6"
  action-orange: "#ff7856"
  surface: "#ffffff"
  app-bg: "#f6f6f9"
  soft-bg: "#f3f3f3"
  border: "#dedede"
  border-strong: "#c4c4c4"
  text-strong: "#101010"
  text-body: "#212529"
  text-muted: "#949494"
  admin-ink: "#181c32"
  backend-info: "#00a3ff"
  success: "#31a24c"
  danger: "#dc3545"
  warning: "#ffc107"
typography:
  display:
    fontFamily: "Poppins, system-ui, -apple-system, Segoe UI, sans-serif"
    fontSize: "42px"
    fontWeight: 600
    lineHeight: 1.24
    letterSpacing: "normal"
  headline:
    fontFamily: "system-ui, -apple-system, Segoe UI, Roboto, Helvetica Neue, Arial, sans-serif"
    fontSize: "27px"
    fontWeight: 500
    lineHeight: 1.37
    letterSpacing: "normal"
  title:
    fontFamily: "system-ui, -apple-system, Segoe UI, Roboto, Helvetica Neue, Arial, sans-serif"
    fontSize: "18px"
    fontWeight: 600
    lineHeight: 1.25
    letterSpacing: "normal"
  body:
    fontFamily: "system-ui, -apple-system, Segoe UI, Roboto, Helvetica Neue, Arial, sans-serif"
    fontSize: "16px"
    fontWeight: 400
    lineHeight: 1.5
    letterSpacing: "normal"
  label:
    fontFamily: "system-ui, -apple-system, Segoe UI, Roboto, Helvetica Neue, Arial, sans-serif"
    fontSize: "14px"
    fontWeight: 500
    lineHeight: 1.25
    letterSpacing: "normal"
rounded:
  xs: "2px"
  sm: "5px"
  md: "10px"
  lg: "20px"
  pill: "50px"
spacing:
  xs: "5px"
  sm: "10px"
  md: "15px"
  lg: "20px"
  xl: "30px"
  section: "50px"
components:
  button-primary:
    backgroundColor: "{colors.primary-purple}"
    textColor: "{colors.surface}"
    rounded: "{rounded.pill}"
    padding: "10px 32px"
    typography: "{typography.label}"
  button-primary-hover:
    backgroundColor: "{colors.primary-purple-deep}"
    textColor: "{colors.surface}"
    rounded: "{rounded.pill}"
    padding: "10px 32px"
    typography: "{typography.label}"
  button-auth-accent:
    backgroundColor: "{colors.action-orange}"
    textColor: "{colors.surface}"
    rounded: "{rounded.sm}"
    padding: "10px 32px"
    typography: "{typography.title}"
  input-default:
    backgroundColor: "{colors.soft-bg}"
    textColor: "{colors.text-body}"
    rounded: "{rounded.pill}"
    padding: "10px 20px"
    typography: "{typography.body}"
  card-default:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.text-body}"
    rounded: "{rounded.sm}"
    padding: "20px"
---

# Design System: Sociopro

## 1. Overview

**Creative North Star: "The Reliable Community Console"**

Sociopro is a task-heavy social product, not a brand showcase. Its design system should feel like a familiar community console: posts, sidebars, cards, forms, tables, badges, and controls are recognizable first, expressive second. The app can carry personality through the purple/orange accent vocabulary and rounded social controls, but the interface earns trust through consistency, readable density, and clear state handling.

The current system is a pragmatic blend of Bootstrap 5, legacy custom CSS, Vite/SCSS entrypoints, and Blade templates. Public social pages use a system sans stack, white cards, gray page surfaces, purple actions, orange auth CTAs, and rounded-pill inputs. Backend screens lean on Poppins, a light gray dashboard canvas, pastel metric cards, blue admin actions, and compact utility classes.

This system explicitly rejects generic SaaS landing-page polish, decorative glass, neon gradients, novelty motion, and hidden state. New UI should consolidate the existing vocabulary rather than introduce another visual dialect.

**Key Characteristics:**
- Dense social-app layout with left navigation, central content, and right contextual panels.
- Purple is the main public action color; blue is mostly an admin/dashboard action color.
- Surfaces stay white or very light gray, with borders and subtle shadows doing practical separation.
- Rounded social controls are common, but cards remain modestly rounded.
- Form states, auth controls, payment controls, and destructive actions must be explicit.

## 2. Colors

The palette is restrained and product-first: mostly white and gray surfaces, one strong public purple, one warm auth/CTA orange, and semantic colors for state.

### Primary
- **Public Action Purple**: Used for primary timeline/social actions, links, active states, like states, social-share hover states, and focus outlines.
- **Deep Chat Purple**: Used for stronger chat/action surfaces where the primary purple needs more contrast.
- **Soft Purple Wash**: Used for reply bubbles, social share chips, highlighted low-emphasis surfaces, and calm selected states.

### Secondary
- **Auth Accent Orange**: Used for login/register calls to action and warm onboarding emphasis. Keep it rare; it should not compete with the public purple on logged-in product screens.
- **Admin Action Blue**: Used in backend tables, admin buttons, active admin navigation, and dashboard utility states.

### Tertiary
- **Status Green**: Success, active/online, completed, positive states.
- **Status Red**: Danger, destructive actions, validation errors, failed states.
- **Status Yellow**: Warnings, pending states, attention without danger.

### Neutral
- **White Surface**: Cards, widgets, modals, auth panels, content containers.
- **App Canvas**: Backend dashboard background and light product page canvas.
- **Soft Field Gray**: Inputs, muted form backgrounds, product-form fields, and low-emphasis containers.
- **Standard Border**: Dividers, tables, media frames, and subtle card boundaries.
- **Strong Text**: Headings, card titles, primary labels.
- **Muted Text**: Metadata, placeholders, timestamps, helper copy. Do not use muted text for important instructions unless contrast is checked.

### Named Rules

**The One Accent Per Surface Rule.** A single screen can emphasize public purple, auth orange, or admin blue, but not all three as competing primary actions.

**The White Card Contract.** Cards and widgets rest on white or near-white surfaces. If a panel becomes colorful, it must communicate a status or selected state, not decoration.

## 3. Typography

**Display Font:** Poppins for backend/dashboard display contexts, with system sans fallback.
**Body Font:** Bootstrap/system sans for public product screens, with Segoe UI, Roboto, Helvetica Neue, and Arial fallbacks.
**Label/Mono Font:** No distinct mono label system is established; use the body stack for labels.

**Character:** Typography should be familiar, compact, and utilitarian. Use font weight and spacing to create hierarchy; do not add display fonts to product labels or dense social controls.

### Hierarchy
- **Display** (600, 42px, 1.24): Backend dashboards and major admin headings. Avoid the legacy 90px h1 scale for product workflow screens.
- **Headline** (500, 27px, 1.37): Page titles, large widget titles, and auth headings when a screen has one main task.
- **Title** (600, 18px, 1.25): Card headings, widget titles, sidebar section headers, table summaries.
- **Body** (400, 16px, 1.5): Forms, posts, profile text, modal content, helper text. Long prose should stay near 65-75ch.
- **Label** (500, 14px, 1.25): Form labels, metadata labels, nav labels, compact admin controls.

### Named Rules

**The Familiar Sans Rule.** Product labels, buttons, tables, and navigation stay in the existing sans vocabulary. Do not introduce decorative fonts into task surfaces.

**The Dense Legibility Rule.** Dense screens are allowed, but text smaller than 14px needs a specific metadata or badge reason.

## 4. Elevation

Sociopro uses a hybrid of flat card layering, thin borders, and occasional soft shadows. Public feed cards and media containers are mostly white blocks with small radii and light borders. Backend dashboard panels use light pastel surfaces and gentle shadows for grouped controls.

### Shadow Vocabulary
- **Admin Soft Panel** (`0 5px 20px rgba(0, 0, 0, .06)`): Payment settings, subscription controls, and dashboard panels that need a lifted management feel.
- **Flat Feed Card** (`box-shadow: none` with white background and optional `1px solid #dedede` border): Timeline, media, and content cards at rest.
- **Overlay Gradient** (`linear-gradient(0deg, #000, transparent)`): Image/video/blog overlays where text must sit on media.

### Named Rules

**The Flat Until Needed Rule.** Default content cards are flat. Add shadow only for admin grouping, hover affordance, or overlays that need separation.

**The No Ghost Card Rule.** Do not combine a thin border with a large decorative blur. Pick a border or a small functional shadow.

## 5. Components

### Buttons

Buttons are Bootstrap-compatible and action-specific. Public primary actions are purple pills; auth entry actions are orange rectangular buttons; backend actions are blue or semantic Bootstrap buttons.

- **Shape:** Public action buttons are rounded-pill controls (50px). Auth CTA buttons use modest 5-10px rounding. Cards and panels should not exceed 20px unless they are circular avatars or pills.
- **Primary:** Public purple background with white text, compact padding around 10px 32px, medium label weight.
- **Hover / Focus:** Darken to deep purple or preserve the semantic color, with visible focus outline. Never remove focus without replacement.
- **Secondary / Ghost / Tertiary:** Use transparent or white backgrounds with clear borders; do not use low-contrast gray text for actionable labels.
- **Reusable Blade component:** Use `<x-ui.button>` for new Blade buttons that should stay aligned with the extracted variants. Supported variants are `public-primary`, `public-secondary`, `backend-primary`, and `auth-primary`; use `block` for full-width actions and `size="sm"` / `size="lg"` for Bootstrap-compatible sizing.

### Chips

Chips appear as reaction states, badges, social filters, and status markers.

- **Style:** Reaction chips use vivid semantic colors for meaning; quiet badges use soft fills with dark text.
- **State:** Selected and active states must be visually distinct beyond color where possible, using weight, icon state, or position.

### Cards / Containers

Cards and widgets are the backbone of the feed, sidebars, auth forms, and dashboards.

- **Corner Style:** Small card radius (5px) is the default; 10px is acceptable for auth fields and controls; 20px is reserved for prominent dashboard modules.
- **Background:** Use white surfaces over app gray, or pastel status surfaces in admin dashboard metric cards.
- **Shadow Strategy:** Public content stays flat; admin panels may use the Admin Soft Panel shadow.
- **Border:** Use standard border gray for media frames, table boundaries, and quiet separation.
- **Internal Padding:** 15-20px is the normal card rhythm; 30px is for large auth or dashboard sections.

### Inputs / Fields

Inputs are practical, high-contrast controls with rounded social styling.

- **Style:** Public search and message inputs use soft gray or transparent backgrounds with rounded-pill shape. Auth inputs use transparent backgrounds with a 2px strong gray border and icon-leading spacing.
- **Focus:** Purple focus outline or border shift. Password toggle buttons must preserve a visible focus state.
- **Error / Disabled:** Error text uses semantic red and appears directly after the field. Disabled controls must look disabled and not rely only on pointer events.
- **Reusable Blade components:** Use `<x-ui.auth-text-field>` for auth text/email/name fields and `<x-ui.auth-password-field>` for auth password fields with the shared accessible reveal control. Keep the shared footer script as the single behavior owner for password toggles.

### Navigation

Navigation is left/sidebar-heavy with compact icons, route-based active states, and mobile offcanvas behavior.

- **Style:** Use icon plus label in a compact row. Active state should be clear and stable across route groups.
- **Typography:** 14-16px labels with medium weight.
- **Mobile:** Use Bootstrap offcanvas and 44px touch targets where practical.
- **State:** Hover, active, focus, unread badges, and dropdown states must be visible and keyboard reachable.

### Signature Components

**Timeline Shell.** The canonical logged-in layout is a three-column structure: left module navigation, center feed/content, and right contextual sidebar. New logged-in surfaces should fit this shell unless a workflow needs a dedicated full-width layout.

**Auth Split Screen.** Login and registration use an image/brand half plus a task form half. Preserve clear labels, visible validation, password reveal controls, and simple primary actions.

**Admin Dashboard Panel.** Backend screens use Poppins, light dashboard backgrounds, compact controls, and blue/semantic action colors. Admin UI can be denser than the public feed, but it must make destructive actions and status changes explicit.

## 6. Do's and Don'ts

### Do:

- **Do** use Public Action Purple for primary public social actions and Admin Action Blue for backend management actions.
- **Do** keep Blade components and views presentation-only; new UI should consume prepared data and not query in templates.
- **Do** use white cards on light gray surfaces for feeds, sidebars, auth, and dashboard panels.
- **Do** preserve Bootstrap-compatible markup, forms, dropdowns, modals, offcanvas navigation, and table conventions unless a replacement is deliberately designed.
- **Do** show clear focus, hover, disabled, loading, empty, validation, and destructive states.
- **Do** keep dense screens readable with 14px minimum control text, 16px body text, and 15-20px card padding.

### Don't:

- **Don't** make the product feel like a generic SaaS landing page or AI-generated marketing template.
- **Don't** use glassmorphism, neon gradients, decorative grid backgrounds, or novelty motion as the default app language.
- **Don't** add gradient text, large side-stripe card borders, decorative blur-heavy ghost cards, or oversized 32px-plus card radii.
- **Don't** mix public purple, auth orange, and admin blue as equal primary actions on one screen.
- **Don't** hide security, authorization, destructive actions, disabled states, or validation errors behind subtle styling.
- **Don't** introduce a new visual dialect for one module when a Bootstrap-compatible card, button, form, table, modal, or offcanvas pattern already exists.
