<?php

require __DIR__ . '/../lib/bootstrap.php';

$pdo = shop_db();
$id = (int)($_GET['id'] ?? 0);

shop_header($id > 0 ? '订单详情' : '我的订单');

if ($id <= 0) {
    $orders = $pdo->query('SELECT * FROM orders ORDER BY id DESC LIMIT 10')->fetchAll();
    ?>
    <h1>我的订单</h1>
    <?php if ($orders === []): ?>
      <p class="note">还没有订单。<a href="index.php">去挑几件商品</a></p>
    <?php else: ?>
      <table>
        <thead><tr><th>#</th><th>时间</th><th>收货人</th><th>实付</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($orders as $order): ?>
          <tr>
            <td><?= (int)$order['id'] ?></td>
            <td><?= e($order['created_at']) ?></td>
            <td><?= e($order['customer']) ?></td>
            <td class="num">¥<?= money((float)$order['total']) ?></td>
            <td><a class="btn ghost" href="order.php?id=<?= (int)$order['id'] ?>">查看</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
    <?php
    shop_footer();
    return;
}

$stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
$stmt->execute([$id]);
$order = $stmt->fetch();

if ($order === false) {
    ?>
    <div class="flash">找不到订单 #<?= $id ?></div>
    <p><a class="btn ghost" href="order.php">返回订单列表</a></p>
    <?php
    shop_footer();
    return;
}

$stmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ? ORDER BY id');
$stmt->execute([$id]);
$items = $stmt->fetchAll();

$paid = (string)($_GET['paid'] ?? '') === '1';
?>
<h1>订单 #<?= (int)$order['id'] ?></h1>

<?php if ($paid): ?>
  <div class="flash ok">支付成功（演示假支付，未接任何支付网关），订单已写入 SQLite。</div>
<?php endif; ?>

<p class="note">
  下单时间 <?= e($order['created_at']) ?> ·
  收货人 <?= e($order['customer']) ?> ·
  卡号尾号 <?= e($order['card_last4']) ?> ·
  会员 <?= e($order['level']) ?><?= $order['coupon'] !== null ? ' · 券 ' . e($order['coupon']) : '' ?>
</p>

<table>
  <thead><tr><th>商品</th><th>单价</th><th>数量</th><th class="num">小计</th></tr></thead>
  <tbody>
  <?php foreach ($items as $item): ?>
    <tr>
      <td><?= e($item['name']) ?><br><span class="note"><?= e($item['sku']) ?></span></td>
      <td>¥<?= money((float)$item['price']) ?></td>
      <td><?= (int)$item['qty'] ?></td>
      <td class="num">¥<?= money((float)$item['amount']) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
  <tfoot>
    <tr><td colspan="3">小计</td><td class="num">¥<?= money((float)$order['subtotal']) ?></td></tr>
    <tr><td colspan="3">会员折扣</td><td class="num">-¥<?= money((float)$order['member_discount']) ?></td></tr>
    <tr><td colspan="3">满减</td><td class="num">-¥<?= money((float)$order['threshold_discount']) ?></td></tr>
    <tr><td colspan="3">优惠券</td><td class="num">-¥<?= money((float)$order['coupon_discount']) ?></td></tr>
    <tr><td colspan="3">运费</td><td class="num">+¥<?= money((float)$order['shipping']) ?></td></tr>
    <tr><td colspan="3">实付</td><td class="num"><span class="total">¥<?= money((float)$order['total']) ?></span></td></tr>
  </tfoot>
</table>

<p class="row" style="margin-top:18px">
  <a class="btn ghost" href="index.php">继续购物</a>
  <a class="btn ghost" href="order.php">订单列表</a>
</p>
<?php
shop_footer();
