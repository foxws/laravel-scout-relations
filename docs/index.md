---
title: Introduction
metadata:
  role: Search
  eyebrow: "Laravel Scout · Auto Re-index · Relations"
  desc: "Keep related Laravel Scout search indexes fresh automatically."
  requires: "PHP ^8.4"
  laravel: "12.x / 13.x"
  licence: MIT
---

# Introduction

This package keeps your Laravel Scout search index up to date automatically.

When you save or delete a model, this package can also re-index its related
models — the ones connected to it through an Eloquent relationship. For
example, if you save an `Author`, this package automatically queues that
author's `Post` records for re-indexing. You don't need to write any extra
code to keep the two in sync.

## Requirements

- PHP 8.4+
- Laravel 12+
- [Laravel Scout](https://laravel.com/docs/scout)

Continue to [Installation](./installation.md) to get started.
