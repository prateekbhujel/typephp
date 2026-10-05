<?php

/**
 * 公共引导：会话、SQLite、购物车、视图助手。
 *
 * 这个文件是「非编译代码」—— 随 embedded-files 内嵌进 shop_fpm，由各页面 require。
 * 会话、数据库、HTML 都放在这里；AOT 编译的只有 src/ 下的价格计算与商品数据。
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// 缓冲输出：这样 header()/redirect 不会被之前的告警或空白打断。
ob_start();

// ---------------------------------------------------------------- 数据库

function shop_db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $path = getenv('SHOP_DB');
    if ($path === false || $path === '') {
        // __DIR__ 指向构建时的 lib/ 目录，数据库固定落在项目根的 var/ 下，
        // 不放进 docroot（getcwd() 在请求里是脚本目录，不能用来定位数据）。
        $path = dirname(__DIR__) . '/var/shop.db';
    }
    $dir = dirname($path);
    if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
        throw new RuntimeException("无法创建数据库目录: {$dir}");
    }

    $pdo = new PDO('sqlite:' . $path, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA journal_mode = WAL');

    shop_migrate($pdo);

    return $pdo;
}

function shop_migrate(PDO $pdo): void
{
    $pdo->exec('CREATE TABLE IF NOT EXISTS products (
        sku         TEXT PRIMARY KEY,
        name        TEXT NOT NULL,
        price       REAL NOT NULL,
        digital     INTEGER NOT NULL DEFAULT 0,
        stock       INTEGER NOT NULL DEFAULT 0,
        description TEXT NOT NULL DEFAULT \'\'
    )');
    $pdo->exec('CREATE TABLE IF NOT EXISTS orders (
        id                 INTEGER PRIMARY KEY AUTOINCREMENT,
        created_at         TEXT NOT NULL,
        customer           TEXT NOT NULL,
        card_last4         TEXT NOT NULL,
        level              TEXT NOT NULL,
        coupon             TEXT,
        subtotal           REAL NOT NULL,
        member_discount    REAL NOT NULL,
        threshold_discount REAL NOT NULL,
        coupon_discount    REAL NOT NULL,
        shipping           REAL NOT NULL,
        total              REAL NOT NULL
    )');
    $pdo->exec('CREATE TABLE IF NOT EXISTS order_items (
        id       INTEGER PRIMARY KEY AUTOINCREMENT,
        order_id INTEGER NOT NULL,
        sku      TEXT NOT NULL,
        name     TEXT NOT NULL,
        price    REAL NOT NULL,
        qty      INTEGER NOT NULL,
        amount   REAL NOT NULL
    )');

    // 商品以 AOT 编译的 shop_catalog() 为准：每次启动都按 SKU 同步一遍，
    // 改了 catalog.php 重新构建即可生效，不用手动清库。
    $upsert = $pdo->prepare(
        'INSERT INTO products (sku, name, price, digital, stock, description) VALUES (?, ?, ?, ?, ?, ?)
         ON CONFLICT(sku) DO UPDATE SET
             name = excluded.name,
             price = excluded.price,
             digital = excluded.digital,
             stock = excluded.stock,
             description = excluded.description'
    );
    foreach (shop_catalog() as $product) {
        $upsert->execute([
            $product['sku'],
            $product['name'],
            $product['price'],
            $product['digital'],
            $product['stock'],
            $product['desc'],
        ]);
    }
}

function shop_products(): array
{
    return shop_db()->query('SELECT * FROM products ORDER BY sku')->fetchAll();
}

function shop_product(string $sku): ?array
{
    $stmt = shop_db()->prepare('SELECT * FROM products WHERE sku = ?');
    $stmt->execute([$sku]);
    $row = $stmt->fetch();

    return $row === false ? null : $row;
}

// ---------------------------------------------------------------- 购物车（会话）

function shop_cart(): array
{
    $cart = $_SESSION['cart'] ?? null;

    return is_array($cart) ? $cart : [];
}

function shop_cart_add(string $sku, int $qty = 1): void
{
    $cart = shop_cart();
    $cart[$sku] = ($cart[$sku] ?? 0) + max(1, $qty);
    $_SESSION['cart'] = $cart;
}

function shop_cart_set(string $sku, int $qty): void
{
    $cart = shop_cart();
    if ($qty <= 0) {
        unset($cart[$sku]);
    } else {
        $cart[$sku] = $qty;
    }
    $_SESSION['cart'] = $cart;
}

function shop_cart_remove(string $sku): void
{
    $cart = shop_cart();
    unset($cart[$sku]);
    $_SESSION['cart'] = $cart;
}

function shop_cart_clear(): void
{
    $_SESSION['cart'] = [];
}

function shop_cart_count(): int
{
    return (int)array_sum(shop_cart());
}

/** 把会话里的购物车拼成 shop_pricing() 需要的行。 */
function shop_cart_lines(): array
{
    $lines = [];
    foreach (shop_cart() as $sku => $qty) {
        $product = shop_product((string)$sku);
        if ($product === null) {
            continue;
        }
        $lines[] = [
            'sku' => $product['sku'],
            'name' => $product['name'],
            'price' => (float)$product['price'],
            'qty' => (int)$qty,
            'digital' => (int)$product['digital'],
        ];
    }

    return $lines;
}

// ---------------------------------------------------------------- 会员等级与优惠券

function shop_level(): string
{
    return (string)($_SESSION['level'] ?? 'guest');
}

function shop_coupon(): ?string
{
    $coupon = $_SESSION['coupon'] ?? null;

    return ($coupon === null || $coupon === '') ? null : (string)$coupon;
}

/** 当前购物车的完整报价（由 AOT 编译的 shop_pricing() 计算）。 */
function shop_quote(): array
{
    return shop_pricing(shop_cart_lines(), [
        'level' => shop_level(),
        'coupon' => shop_coupon(),
        'shipping' => shop_shipping_fee(),
    ]);
}

// ---------------------------------------------------------------- 视图助手

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function money(float $value): string
{
    return number_format($value, 2);
}

function shop_redirect(string $url): void
{
    header('Location: ' . $url, true, 302);
    exit;
}

function shop_header(string $title): void
{
    $count = shop_cart_count();
    $level = shop_level();
    $coupon = shop_coupon();
    header('Content-Type: text/html; charset=utf-8');
    // 明确声明不要嗅探内容类型，避免浏览器把 HTML 当别的类型处理。
    header('X-Content-Type-Options: nosniff');
    ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta charset="utf-8">
<title><?= e($title) ?> · TypePHP 迷你商城</title>
<style>
  :root { color-scheme: light; }
  * { box-sizing: border-box; }
  body { margin: 0; font: 15px/1.6 -apple-system, "Segoe UI", "PingFang SC", "Microsoft YaHei", sans-serif;
         color: #1f2430; background: #f5f6f8; }
  header { background: #1f2430; color: #fff; padding: 14px 24px; display: flex; align-items: center; gap: 20px; }
  header a { color: #fff; text-decoration: none; }
  header .brand { font-weight: 700; letter-spacing: .3px; }
  header .meta { margin-left: auto; font-size: 13px; color: #c3c9d4; }
  header .meta b { color: #fff; }
  main { max-width: 940px; margin: 24px auto 64px; padding: 0 24px; }
  h1 { font-size: 22px; margin: 0 0 16px; }
  h2 { font-size: 17px; margin: 24px 0 10px; }
  .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 16px; }
  .card { background: #fff; border: 1px solid #e3e6ec; border-radius: 10px; padding: 16px; }
  .card h3 { margin: 0 0 6px; font-size: 16px; }
  .card .desc { color: #6b7280; font-size: 13px; min-height: 40px; }
  .card .price { font-size: 20px; font-weight: 700; color: #d92d20; margin: 8px 0 12px; }
  .card .digital { display: inline-block; font-size: 12px; color: #0b7285; background: #e3f4f7; border-radius: 4px; padding: 1px 6px; }
  table { width: 100%; border-collapse: collapse; background: #fff; border: 1px solid #e3e6ec; border-radius: 10px; overflow: hidden; }
  th, td { padding: 10px 12px; border-bottom: 1px solid #eef0f4; text-align: left; }
  th { background: #fafbfc; font-weight: 600; font-size: 13px; color: #4b5563; }
  td.num, th.num { text-align: right; }
  tfoot td { font-weight: 600; background: #fafbfc; }
  .btn { display: inline-block; border: 0; border-radius: 8px; padding: 8px 14px; font-size: 14px;
         background: #2f6fed; color: #fff; text-decoration: none; cursor: pointer; }
  .btn.ghost { background: #eef1f6; color: #1f2430; }
  .btn.danger { background: #fdecec; color: #d92d20; }
  .row { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
  .chips { display: flex; gap: 8px; flex-wrap: wrap; }
  .chip { border: 1px solid #d6dae2; background: #fff; border-radius: 999px; padding: 5px 12px;
          font-size: 13px; text-decoration: none; color: #1f2430; }
  .chip.on { border-color: #2f6fed; background: #eaf0fe; color: #2f6fed; font-weight: 600; }
  .total { font-size: 24px; font-weight: 700; color: #d92d20; }
  .note { color: #6b7280; font-size: 13px; }
  .flash { background: #fff4e5; border: 1px solid #ffd9a8; color: #8a5300; padding: 10px 14px;
           border-radius: 8px; margin-bottom: 16px; }
  .ok { background: #e8f7ee; border-color: #b7e4c7; color: #12633a; }
  form.stack { background: #fff; border: 1px solid #e3e6ec; border-radius: 10px; padding: 18px; }
  form.stack label { display: block; font-size: 13px; color: #4b5563; margin: 10px 0 4px; }
  form.stack input { width: 100%; padding: 9px 11px; border: 1px solid #d6dae2; border-radius: 8px; font-size: 14px; }
</style>
</head>
<body>
<header>
  <a class="brand" href="index.php">TypePHP 迷你商城</a>
  <a href="index.php">商品</a>
  <a href="cart.php">购物车 (<?= $count ?>)</a>
  <a href="checkout.php">结算</a>
  <a href="order.php">订单</a>
  <span class="meta">
    会员 <b><?= e($level) ?></b>
    <?php if ($coupon !== null): ?>· 券 <b><?= e($coupon) ?></b><?php endif; ?>
  </span>
</header>
<main>
    <?php
}

function shop_footer(): void
{
    ?>
</main>
</body>
</html>
    <?php
}
