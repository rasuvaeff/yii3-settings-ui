# Changelog

## 2.0.0 — 2026-10-17

- Actions read the `{key}` route argument from constructor-injected `Yiisoft\Router\CurrentRoute`
  instead of the `#[RouteArgument]` attribute. The attribute is resolved only when the host app's
  middleware dispatcher carries a parameters resolver (`Yiisoft\Input\Http\HydratorAttributeParametersResolver`
  from `yiisoft/input-http`, the `yiisoft/app` template wiring); without it every route with an
  argument (edit, update, reset) answered 400 because the action was never invoked. `yiisoft/input-http`
  is no longer needed at all. Breaking: `Yii\Edit\Action`, `Yii\Update\Action` and `Yii\Reset\Action`
  take `CurrentRoute` in the constructor and no longer accept a hydrated `$key` parameter.
  Fixes #15.

## 1.0.2 — 2026-06-30

- Add `/benchmarks` and `/Makefile` to `.gitattributes` export-ignore.

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 1.0.1 — 2026-06-27

- Migrate test suite from PHPUnit to Testo. Internal change, no public API impact.

## 1.0.0 — 2026-06-19

Initial release: a Bootstrap 5 admin UI for managing `rasuvaeff/yii3-settings`
runtime settings.



