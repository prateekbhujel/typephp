# promo-ext

Compile a piece of ordinary PHP business logic into a loadable PHP extension. It implements a
small e-commerce promotion/pricing engine and exposes it to any host PHP process through
`extension=`.

The extension exports:

- `PROMO_VERSION` — built-in rule/version constant.
- `promo_rules(): array` — the built-in promotion rules.
- `promo_quote(array $cart, array $context = []): array` — cart discount and payable amount.

> This example only covers the **extension** target. To compile the same logic into a
> self-contained php-fpm binary, see `examples/shop-fpm/` — a full multi-page web app that
> compiles this very `src/promo.php` (via `sources: ../promo-ext/src`), so there is no second
> copy of the rules.

## Layout

```
promo-ext/
├── project.yml      # mode: ext
├── src/promo.php    # extension source (AOT-compiled, not shipped)
├── demo.php         # host-side usage example (not compiled)
└── README.md
```

`demo.php` is not part of `sources`, so it is never compiled: it plays the role of a host project
that only has the `.so` and none of the PHP sources.

## Build

From the `compiler` directory:

```sh
./tpc examples/promo-ext/project.yml --no-progress -j4
```

This produces `promo.so` in the current directory. The module name registered inside PHP is
`typephp_promo` (the `typephp_` prefix is added by the compiler); the file name stays `promo.so`.

Load and run:

```sh
php -d extension="$PWD/promo.so" examples/promo-ext/demo.php
php -d extension="$PWD/promo.so" -m | grep typephp_promo
php -d extension="$PWD/promo.so" --ri typephp_promo
```

## Notes

- The extension links PHPX (`libphpx.so`) only. Zend/PHP symbols are resolved from the host SAPI,
  so the host PHP must match the ABI it was built against (version, ZTS/NTS, debug flags).
- Because it depends on `libphpx.so` at runtime, keep the PHPX library available on the target
  machine (an rpath is written at build time).
- `./tpc` is the packaged compiler. If your working tree is newer than that binary, use
  `php bin/tpc.php` instead so the build reflects the current source.
