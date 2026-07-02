# Blade Component Guide

Generated: 2026-07-02

Use Blade components when markup is reused, has a stable design contract, or needs consistent accessibility behavior. Do not create components just to hide business logic; move business logic to controllers, actions, services, policies, requests, or view models first.

## Component Placement

- Anonymous components: `resources/views/components`
- Class-based components: `app/View/Components` with views in `resources/views/components`
- Frontend partials that are not yet componentized may remain under `resources/views/frontend`, but new reusable UI should prefer components.

## Component Contract

- Declare props with `@props` at the top of anonymous components.
- Use named slots for structured content such as titles, actions, footers, and descriptions.
- Keep component props presentation-oriented: labels, URLs, state flags, IDs, and already-computed counts.
- Do not query models, call aggregates, or perform authorization decisions inside component templates.
- Use escaped output by default and document any trusted HTML slot.

## Accessibility Checklist

- Links navigate; buttons perform actions.
- Form components expose `id`, `name`, `label`, `required`, `disabled`, `help`, and error state where applicable.
- Icon-only controls need an accessible name.
- Decorative icons/images use `aria-hidden="true"` or empty `alt`.
- Components that open modals, dropdowns, tabs, or accordions must preserve keyboard behavior and focus order.

## Migration Approach

1. Add a regression or rendering test for the existing markup.
2. Extract the smallest repeated structure into a component.
3. Keep existing CSS classes and JS hooks unless the test suite covers the replacement.
4. Replace call sites gradually and verify the rendered HTML.

## Page Profile Candidate Components

The page profile sidebar/header were cleaned up without introducing components to keep the change reviewable. Future safe component candidates:

- Page profile action buttons.
- Page info list rows.
- Suggested page cards.
- Media thumbnail tiles.
