<?php

require __DIR__ . '/../lib/bootstrap.php';

$products = shop_products();

shop_header('商品列表');
?>
<h1>商品列表</h1>
<p class="note">
  共 <?= count($products) ?> 件商品。会员等级和优惠券在<b>购物车</b>页面选择，折扣会实时算进结算金额。
  带「可下载」标记的商品不计运费。
</p>

<div class="grid">
<?php foreach ($products as $product): ?>
  <div class="card">
    <h3><?= e($product['name']) ?></h3>
    <div class="desc"><?= e($product['description']) ?></div>
    <?php if ((int)$product['digital'] === 1): ?>
      <span class="digital">可下载 · 不计运费</span>
    <?php else: ?>
      <span class="note">库存 <?= (int)$product['stock'] ?></span>
    <?php endif; ?>
    <div class="price">¥<?= money((float)$product['price']) ?></div>
    <div class="row">
      <a class="btn" href="cart.php?action=add&amp;sku=<?= e($product['sku']) ?>">加入购物车</a>
    </div>
  </div>
<?php endforeach; ?>
</div>
<?php
shop_footer();
