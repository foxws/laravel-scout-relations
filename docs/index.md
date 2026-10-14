---
title: Introduction
metadata:
  role: Search
  group: search
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

> [!TIP]
> Let your AI agent do the hard parts. This package ships a [Laravel Boost](https://laravel.com/docs/boost) skill, `scout-relations-development`, that teaches your agent how to install, configure and use Laravel Scout Relations. Install Boost and select the skill when prompted:
>
> ```bash
> composer require laravel/boost --dev
> php artisan boost:install
> ```
>
> Already using Boost? Run `php artisan boost:update --discover` to pick up the skill. Then just ask, for example: *"Re-index an author's posts in Scout whenever the author is saved."*

Continue to [Installation](./installation.md) to get started.
