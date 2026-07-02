---
name: Security report
about: Report a security concern (prefer private disclosure for exploitable issues)
title: "[Security] "
labels: security
---

> For an exploitable vulnerability, prefer private disclosure to the maintainers over a public issue.

## Category

- [ ] Authentication / session
- [ ] Authorization / IDOR
- [ ] Injection (SQL, command, SSRF)
- [ ] XSS / output encoding
- [ ] File upload / download
- [ ] CSRF / unsafe method
- [ ] Secrets / configuration exposure
- [ ] Webhook / payment callback
- [ ] Rate limit / abuse
- [ ] Other

## Description

What is the risk and where (file paths / routes)?

## Impact

Who is affected and what can an attacker achieve?

## Reproduction notes

<!-- Do not include working exploit payloads against production data. Use safe local/test examples only. -->

## Suggested remediation

## Required validation

- [ ] Regression test added
- [ ] Authorization failure asserted
- [ ] Validation failure asserted
- [ ] Sensitive output hidden
- [ ] External services faked
- [ ] Logs checked for secrets/personal data

## Deployment notes

- Config/env changes:
- Cache/queue/scheduler impact:
- Rollback notes:
