# Product

## Register

product

## Users

Sociopro serves authenticated social-community users, creators, sellers, group and page owners, event organizers, job and marketplace participants, and administrators maintaining a legacy Laravel social platform. Users arrive to complete practical social workflows: read and publish posts, chat, manage profile media, join groups, browse pages, handle events, use marketplace/job modules, manage payments, and moderate or configure the system.

## Product Purpose

Sociopro exists as a server-rendered social application that consolidates community timeline, messaging, media, marketplace, jobs, events, fundraising, paid content, and administration in one Laravel codebase. Success means the app feels stable, familiar, secure, and fast enough for daily work while the legacy implementation is hardened incrementally without breaking public routes or response contracts.

## Brand Personality

Trusted utility, social familiarity, and steady modernization. The product voice should be clear, plain, and reassuring. It should feel like a dependable community tool that is being carefully improved, not like a speculative redesign trying to impress at the expense of workflow clarity.

## Anti-references

- Do not make the product feel like a generic SaaS landing page or AI-generated marketing template.
- Do not use glassmorphism, neon gradients, decorative grid backgrounds, or novelty motion as the default app language.
- Do not turn dense social and admin workflows into over-spacious showcase layouts that hide required actions.
- Do not introduce visual patterns that depend on JavaScript-only affordances when Blade and Bootstrap already provide familiar controls.
- Do not make security, authorization, destructive actions, disabled states, or validation errors subtle.

## Design Principles

- Familiar workflows over visual novelty. Users should recognize feeds, cards, sidebars, tables, forms, badges, and modals immediately.
- Safety is part of the interface. Auth, payments, moderation, destructive actions, and settings need explicit states, confirmation, and readable feedback.
- Dense but legible. Sociopro has many modules; preserve density, but keep hierarchy, spacing, labels, and empty states understandable.
- Server-rendered clarity. Blade screens should receive prepared data and render predictable HTML with accessible controls.
- Modernize by consolidation. Prefer reusable components, shared CSS vocabulary, and consistent Bootstrap-compatible patterns over one-off page styling.

## Accessibility & Inclusion

Target WCAG AA for new and refactored UI. Keep text contrast readable, preserve visible keyboard focus, use semantic buttons and links, label every form control, provide helpful validation text, and avoid motion that cannot be disabled. Touch targets should be at least 44px on mobile where practical, and color should never be the only signal for status, selection, or errors.
