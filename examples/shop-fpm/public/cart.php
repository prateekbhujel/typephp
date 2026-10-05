<?php

require __DIR__ . '/../lib/bootstrap.php';

// 购物车动作：加购 / 改数量 / 删除 / 清空 / 切换会员等级与优惠券。
// 演示用 GET 链接，浏览器里点一下就能看到金额变化。
$action = (string)($_GET['action'] ?? '');
$sku = (string)($_GET['sku'] ?? '');
$qty = (int)($_GET['qty'] ?? 1);

if ($action === 'add' && $sku !== '') {
    shop_cart_add($sku, $qty);
    shop_redirect('cart.php');
}
if ($action === 'set' && $sku !== '') {
    shop_cart_set($sku, $qty);
    shop_redirect('cart.php');
}
if ($action === 'remove' && $sku !== '') {
    shop_cart_remove($sku);
    shop_redirect('cart.php');
}
if ($action === 'clear') {
    shop_cart_clear();
    shop_redirect('cart.php');
}
if (isset($_GET['level'])) {
    $level = (string)$_GET['level'];
    $_SESSION['level'] = in_array($level, array_keys(shop_levels()), true) ? $level : 'guest';
    shop_redirect('cart.php');
}
if (isset($_GET['coupon'])) {
    $coupon = (string)$_GET['coupon'];
    $_SESSION['coupon'] = ($coupon === '' || $coupon === 'none') ? null : $coupon;
    shop_redirect('cart.php');
}

$quote = shop_quote();
$levels = shop_levels();
$coupons = shop_coupons();

shop_header('购物车');
?>
<h1>购物车</h1>

<h2>会员等级</h2>
<div class="chips">
<?php foreach ($levels as $name => $rate): ?>
  <a class="chip <?= shop_level() === $name ? 'on' : '' ?>" href="cart.php?level=<?= e((string)$name) ?>">
    <?= e((string)$name) ?><?= $rate < 1 ? '（' . rtrim(rtrim(number_format($rate * 100, 0), '0'), '.') . ' 折）' : '' ?>
  </a>
<?php endforeach; ?>
</div>

<h2>优惠券</h2>
<div class="chips">
  <a class="chip <?= shop_coupon() === null ? 'on' : '' ?>" href="cart.php?coupon=none">不使用</a>
<?php foreach ($coupons as $code => $rule): ?>
  <a class="chip <?= shop_coupon() === $code ? 'on' : '' ?>" href="cart.php?coupon=<?= e((string)$code) ?>">
    <?= e((string)$code) ?>
    <?= $rule['type'] === 'percent' ? '（减 ' . (int)($rule['value'] * 100) . '%）' : '（减 ' . money((float)$rule['value']) . '）' ?>
    · 满 <?= money((float)$rule['min']) ?>
  </a>
<?php endforeach; ?>
</div>

<?php if ($quote['items'] === []): ?>
  <h2>购物车是空的</h2>
  <p><a class="btn" href="index.php">去挑几件商品</a></p>
<?php else: ?>

<h2>明细</h2>
<table>
  <thead>
    <tr>
      <th>商品</th><th>单价</th><th>数量</th><th class="num">小计</th><th></th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($quote['items'] as $item): ?>
    <tr>
      <td><?= e($item['name']) ?><br><span class="note"><?= e($item['sku']) ?></span></td>
      <td>¥<?= money($item['price']) ?></td>
      <td>
        <form class="row" method="get" action="cart.php">
          <input type="hidden" name="action" value="set">
          <input type="hidden" name="sku" value="<?= e($item['sku']) ?>">
          <input type="number" name="qty" value="<?= (int)$item['qty'] ?>" min="0" style="width:76px;padding:6px 8px;border:1px solid #d6dae2;border-radius:6px">
          <button class="btn ghost" type="submit">更新</button>
        </form>
      </td>
      <td class="num">¥<?= money((float)$item['amount']) ?></td>
      <td><a class="btn danger" href="cart.php?action=remove&amp;sku=<?= e($item['sku']) ?>">删除</a></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<h2>金额</h2>
<table>
  <tbody>
    <tr><td>商品小计</td><td class="num">¥<?= money($quote['subtotal']) ?></td></tr>
    <tr><td>会员折扣（<?= e($quote['level']) ?>）</td><td class="num">-¥<?= money($quote['memberDiscount']) ?></td></tr>
    <tr><td>满减</td><td class="num">-¥<?= money($quote['thresholdDiscount']) ?></td></tr>
    <tr>
      <td>优惠券<?= $quote['coupon'] !== null ? '（' . e($quote['coupon']['code']) . '）' : '' ?></td>
      <td class="num">-¥<?= money($quote['couponDiscount']) ?></td>
    </tr>
    <?php if ($quote['coupon'] !== null && $quote['coupon']['applied'] === false): ?>
      <tr><td colspan="2" class="note">券未生效：<?= e($quote['coupon']['reason']) ?></td></tr>
    <?php endif; ?>
    <tr><td>运费</td><td class="num">+¥<?= money($quote['shipping']) ?></td></tr>
  </tbody>
  <tfoot>
    <tr><td>应付</td><td class="num">¥<?= money($quote['total']) ?></td></tr>
  </tfoot>
</table>

<p class="row" style="margin-top:18px">
  <a class="btn" href="checkout.php">去结算</a>
  <a class="btn ghost" href="index.php">继续购物</a>
  <a class="btn danger" href="cart.php?action=clear">清空购物车</a>
</p>

<?php endif; ?>
<?php
shop_footer();
