<?php
// cart.php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/session.php';

$pageTitle = 'My Cart';
$cartItems = [];
$total     = 0;

if (isLoggedIn()) {
    try {
        $pdo = getDB();
        $st  = $pdo->prepare('
            SELECT ci.id AS cart_id, ci.quantity, p.id AS product_id,
                   p.name, p.price, p.original_price, p.emoji, p.color_class,
                   p.free_shipping, p.stock, p.seller_id, p.location, p.image
            FROM cart_items ci
            JOIN products p ON p.id = ci.product_id
            WHERE ci.user_id = ?
            ORDER BY ci.added_at DESC
        ');
        $st->execute([$_SESSION['user_id']]);
        $cartItems = $st->fetchAll();
        foreach ($cartItems as $item) {
            $total += $item['price'] * $item['quantity'];
        }
    } catch (Exception $e) { /* ignore */ }
}

require __DIR__ . '/includes/header.php';
?>

<div class="main">
  <h1 class="page-title">🛒 My Shopping Cart</h1>

  <?php if (!isLoggedIn()): ?>
    <div class="empty-state">
      <div class="empty-icon">🛒</div>
      <h3>Please log in to view your cart</h3>
      <a href="<?= SITE_URL ?>/login.php" class="btn-primary">Log In</a>
    </div>

  <?php elseif (empty($cartItems)): ?>
    <div class="empty-state">
      <div class="empty-icon">🛒</div>
      <h3>Your cart is empty</h3>
      <p>Add some products to get started!</p>
      <a href="<?= SITE_URL ?>/index.php" class="btn-primary">Start Shopping</a>
    </div>

  <?php else: ?>
    <div class="cart-layout">
      <div class="cart-items-col">

        <div class="cart-header-row">
          <label class="cart-check-all">
            <input type="checkbox" id="check-all" onchange="toggleAll(this)" checked>
            <span>Select All (<?= count($cartItems) ?> item<?= count($cartItems)>1?'s':'' ?>)</span>
          </label>
          <button class="btn-link" onclick="deleteSelected()">Delete Selected</button>
        </div>

        <?php foreach ($cartItems as $item):
          $itemTotal = $item['price'] * $item['quantity'];
        ?>
        <div class="cart-item" id="cart-item-<?= $item['cart_id'] ?>">
          <input type="checkbox" class="item-check" data-id="<?= $item['cart_id'] ?>"
                 data-price="<?= $item['price'] ?>" checked onchange="recalcTotal()">
          <a href="<?= SITE_URL ?>/product.php?id=<?= $item['product_id'] ?>">
            <div class="cart-thumb <?= $item['color_class'] ?>">
              <?php if (!empty($item['image'])): ?>
                <img src="<?= UPLOAD_URL . sanitize($item['image']) ?>" alt="<?= sanitize($item['name']) ?>" style="width:100%;height:100%;object-fit:cover;border-radius:8px">
              <?php else: ?>
                <?= $item['emoji'] ?>
              <?php endif; ?>
            </div>
          </a>
          <div class="cart-item-info">
            <a href="<?= SITE_URL ?>/product.php?id=<?= $item['product_id'] ?>" class="cart-item-name">
              <?= sanitize($item['name']) ?>
            </a>
            <div class="cart-item-meta">
              📍 <?= sanitize($item['location']) ?>
              <?php if ($item['free_shipping']): ?>
                &nbsp;<span class="free-ship">FREE Shipping</span>
              <?php endif; ?>
            </div>
            <div class="cart-item-price">
              <span class="product-price"><?= peso((float)$item['price']) ?></span>
              <?php if ($item['original_price'] > $item['price']): ?>
                <span class="product-was"><?= peso((float)$item['original_price']) ?></span>
              <?php endif; ?>
            </div>
          </div>
          <div class="cart-qty-col">
            <div class="qty-stepper">
              <button onclick="updateQty(<?= $item['cart_id'] ?>, <?= $item['product_id'] ?>, -1)">−</button>
              <input type="number" id="qty-<?= $item['cart_id'] ?>" value="<?= $item['quantity'] ?>"
                     min="1" max="<?= $item['stock'] ?>"
                     onchange="setQty(<?= $item['cart_id'] ?>, <?= $item['product_id'] ?>, this.value)">
              <button onclick="updateQty(<?= $item['cart_id'] ?>, <?= $item['product_id'] ?>, 1)">+</button>
            </div>
            <button class="btn-link" style="color:#EE4D2D;margin-top:4px"
                    onclick="removeCartItem(<?= $item['cart_id'] ?>)">Remove</button>
          </div>
          <div class="cart-item-subtotal" id="sub-<?= $item['cart_id'] ?>">
            <?= peso($itemTotal) ?>
          </div>
        </div>
        <?php endforeach; ?>

      </div><!-- /.cart-items-col -->

      <!-- ORDER SUMMARY -->
      <div class="cart-summary-col">
        <div class="cart-summary-card">
          <h3>Order Summary</h3>

          <div class="voucher-input-row">
            <input type="text" id="voucher-input" placeholder="Enter voucher code">
            <button onclick="applyVoucher()">Apply</button>
          </div>
          <div id="voucher-msg" style="font-size:12px;margin-top:4px"></div>

          <div class="summary-row">
            <span>Subtotal</span>
            <span id="summary-subtotal"><?= peso($total) ?></span>
          </div>
          <div class="summary-row">
            <span>Shipping</span>
            <span style="color:#26AA99">FREE</span>
          </div>
          <div class="summary-row" id="discount-row" style="display:none">
            <span>Voucher Discount</span>
            <span style="color:#EE4D2D" id="discount-val">-₱0</span>
          </div>
          <div class="summary-row total-row">
            <span>Total</span>
            <span id="summary-total"><?= peso($total) ?></span>
          </div>

          <button id="checkout-btn" class="btn-primary btn-full" style="margin-top:16px" onclick="checkout()">
            Proceed to Checkout (<?= count($cartItems) ?> items)
          </button>
        </div>
      </div>

    </div><!-- /.cart-layout -->
  <?php endif; ?>
</div>

<script>
const SITE_URL = '<?= SITE_URL ?>';
const cartData = <?= json_encode(array_map(fn($i)=>['id'=>$i['cart_id'],'price'=>(float)$i['price'],'qty'=>$i['quantity']], $cartItems)) ?>;

function recalcTotal(){
  let sub = 0;
  let selected = 0;
  document.querySelectorAll('.item-check').forEach(cb=>{
    const cid = cb.dataset.id;
    const price = parseFloat(cb.dataset.price);
    const qty = parseInt(document.getElementById('qty-'+cid)?.value||1);
    const rowTotal = price * qty;
    if(cb.checked){
      sub += rowTotal;
      selected += 1;
    }
    document.getElementById('sub-'+cid).textContent = '₱' + rowTotal.toLocaleString();
  });
  document.getElementById('summary-subtotal').textContent = '₱' + sub.toLocaleString();
  document.getElementById('summary-total').textContent = '₱' + sub.toLocaleString();
  const btn = document.getElementById('checkout-btn');
  if(btn){
    btn.textContent = `Proceed to Checkout (${selected} item${selected===1?'':'s'})`;
  }
}

function toggleAll(cb){
  document.querySelectorAll('.item-check').forEach(i=>i.checked=cb.checked);
  recalcTotal();
}

function updateQty(cartId, prodId, delta){
  const inp=document.getElementById('qty-'+cartId);
  const newVal=Math.max(1,parseInt(inp.value)+delta);
  inp.value=newVal;
  setQty(cartId,prodId,newVal);
}

function setQty(cartId,prodId,val){
  val=Math.max(1,parseInt(val)||1);
  document.getElementById('qty-'+cartId).value=val;
  fetch(SITE_URL+'/api/cart.php',{
    method:'POST',
    headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'<?= csrfToken() ?>'},
    body:JSON.stringify({action:'update',cart_id:cartId,quantity:val})
  });
  recalcTotal();
}

function removeCartItem(cartId){
  if(!confirm('Remove this item?')) return;
  fetch(SITE_URL+'/api/cart.php',{
    method:'POST',
    headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'<?= csrfToken() ?>'},
    body:JSON.stringify({action:'remove',cart_id:cartId})
  }).then(()=>{
    document.getElementById('cart-item-'+cartId)?.remove();
    recalcTotal();
    updateCartBadge(-1);
  });
}

function deleteSelected(){
  document.querySelectorAll('.item-check:checked').forEach(cb=>{
    removeCartItem(cb.dataset.id);
  });
}

function applyVoucher(){
  const code=document.getElementById('voucher-input').value.trim();
  if(!code) return;
  fetch(SITE_URL+'/api/cart.php',{
    method:'POST',
    headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'<?= csrfToken() ?>'},
    body:JSON.stringify({action:'voucher',code})
  }).then(r=>r.json()).then(data=>{
    const msg=document.getElementById('voucher-msg');
    if(data.discount){
      msg.textContent='✅ '+data.message;
      msg.style.color='#26AA99';
      document.getElementById('discount-row').style.display='flex';
      document.getElementById('discount-val').textContent='-₱'+data.discount.toLocaleString();
    } else {
      msg.textContent='❌ '+data.message;
      msg.style.color='#EE4D2D';
    }
  });
}

function checkout(){
  const selectedIds = Array.from(document.querySelectorAll('.item-check:checked')).map(cb => cb.dataset.id);
  if (selectedIds.length === 0) {
    alert('Please select at least one item to checkout.');
    return;
  }
  window.location = SITE_URL + '/checkout.php?cart_ids=' + selectedIds.join(',');
}

function updateCartBadge(delta){
  const b=document.getElementById('cart-badge');
  if(b){ const n=Math.max(0,parseInt(b.textContent)+delta); b.textContent=n; if(n===0)b.style.display='none'; }
}

document.addEventListener('DOMContentLoaded', recalcTotal);
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
