<?php

require __DIR__ . '/../lib/bootstrap.php';

$errors = [];
$quote = shop_quote();

// 假支付：表单校验通过就直接算支付成功，落订单、清空购物车、跳到订单页。
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer = trim((string)($_POST['customer'] ?? ''));
    $address = trim((string)($_POST['address'] ?? ''));
    $card = (string)preg_replace('/\D+/', '', (string)($_POST['card'] ?? ''));
    $expiry = trim((string)($_POST['expiry'] ?? ''));
    $cvc = (string)preg_replace('/\D+/', '', (string)($_POST['cvc'] ?? ''));

    if ($customer === '') {
        $errors[] = '请填写收货人';
    }
    if ($address === '') {
        $errors[] = '请填写收货地址';
    }
    if (strlen($card) < 12) {
        $errors[] = '卡号至少 12 位（演示用假支付，随便填）';
    }
    if ($expiry === '') {
        $errors[] = '请填写有效期';
    }
    if (strlen($cvc) < 3) {
        $errors[] = 'CVC 至少 3 位';
    }
    if ($quote['items'] === []) {
        $errors[] = '购物车是空的，先去挑几件商品';
    }

    if ($errors === []) {
        $pdo = shop_db();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO orders
                 (created_at, customer, card_last4, level, coupon, subtotal,
                  member_discount, threshold_discount, coupon_discount, shipping, total)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                date('Y-m-d H:i:s'),
                $customer,
                substr($card, -4),
                $quote['level'],
                $quote['coupon']['code'] ?? null,
                $quote['subtotal'],
                $quote['memberDiscount'],
                $quote['thresholdDiscount'],
                $quote['couponDiscount'],
                $quote['shipping'],
                $quote['total'],
            ]);
            $orderId = (int)$pdo->lastInsertId();

            $itemStmt = $pdo->prepare(
                'INSERT INTO order_items (order_id, sku, name, price, qty, amount) VALUES (?, ?, ?, ?, ?, ?)'
            );
            foreach ($quote['items'] as $item) {
                $itemStmt->execute([
                    $orderId,
                    $item['sku'],
                    $item['name'],
                    $item['price'],
                    $item['qty'],
                    $item['amount'],
                ]);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        shop_cart_clear();
        shop_redirect('order.php?id=' . $orderId . '&paid=1');
    }
}

shop_header('结算');
?>
<h1>结算</h1>
<p class="note">这是演示用的<b>假支付</b>：不接任何支付网关，表单校验通过就算付款成功，只把订单写进 SQLite。</p>

<?php foreach ($errors as $error): ?>
  <div class="flash"><?= e($error) ?></div>
<?php endforeach; ?>

<?php if ($quote['items'] === []): ?>
  <p class="row"><a class="btn" href="index.php">去挑几件商品</a></p>
<?php else: ?>

<h2>订单内容</h2>
<table>
  <tbody>
  <?php foreach ($quote['items'] as $item): ?>
    <tr>
      <td><?= e($item['name']) ?> × <?= (int)$item['qty'] ?></td>
      <td class="num">¥<?= money((float)$item['amount']) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
  <tfoot>
    <tr><td>小计</td><td class="num">¥<?= money($quote['subtotal']) ?></td></tr>
    <tr><td>会员折扣</td><td class="num">-¥<?= money($quote['memberDiscount']) ?></td></tr>
    <tr><td>满减</td><td class="num">-¥<?= money($quote['thresholdDiscount']) ?></td></tr>
    <tr><td>优惠券</td><td class="num">-¥<?= money($quote['couponDiscount']) ?></td></tr>
    <tr><td>运费</td><td class="num">+¥<?= money($quote['shipping']) ?></td></tr>
    <tr><td>应付</td><td class="num"><span class="total">¥<?= money($quote['total']) ?></span></td></tr>
  </tfoot>
</table>

<h2>支付信息</h2>
<form class="stack" method="post" action="checkout.php">
  <label for="customer">收货人</label>
  <input id="customer" name="customer" value="<?= e((string)($_POST['customer'] ?? '')) ?>" placeholder="张三">

  <label for="address">收货地址</label>
  <input id="address" name="address" value="<?= e((string)($_POST['address'] ?? '')) ?>" placeholder="上海市浦东新区 xx 路 1 号">

  <label for="card">卡号（假支付，随便填，≥12 位）</label>
  <input id="card" name="card" value="" placeholder="4242 4242 4242 4242" inputmode="numeric">

  <div class="row" style="gap:16px">
    <div style="flex:1">
      <label for="expiry">有效期</label>
      <input id="expiry" name="expiry" value="" placeholder="12/29">
    </div>
    <div style="flex:0 0 120px">
      <label for="cvc">CVC</label>
      <input id="cvc" name="cvc" value="" placeholder="123" inputmode="numeric">
    </div>
  </div>

  <p style="margin-top:18px">
    <button class="btn" type="submit">提交支付</button>
    <a class="btn ghost" href="cart.php">返回购物车</a>
  </p>
</form>

<?php endif; ?>
<?php
shop_footer();
