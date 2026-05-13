<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch cart items
$stmt = $pdo->prepare("
    SELECT c.*, p.name AS product_name, p.price, p.image, p.stock 
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    WHERE c.user_id = ?
");

// Fetch user address info
$userStmt = $pdo->prepare("SELECT city, province FROM users WHERE id = ?");
$userStmt->execute([$user_id]);
$user = $userStmt->fetch();

$city = strtolower(trim($user['city'] ?? ''));
$province = strtolower(trim($user['province'] ?? ''));

// Default delivery fee
$delivery_fee = 0;

// Delivery fee rules
if (in_array($city, ['meycauayan','marilao','bocaue','santa maria']) && $province === 'bulacan') {
    $delivery_fee = 500;
} elseif (in_array($city, ['san mateo','montalban','rodriguez']) && $province === 'rizal') {
    $delivery_fee = 500;
} elseif (
    ($province === 'bulacan' && in_array($city, ['malolos','guiguinto','plaridel','baliuag','san rafael'])) ||
    ($province === 'rizal' && in_array($city, ['antipolo','cainta','taytay','binangonan','teresa','cardona','morong'])) ||
    ($province === 'laguna' && in_array($city, ['biñan','san pedro','santa rosa']))
) {
    $delivery_fee = 1000;
} elseif ($province === 'cavite' && in_array($city, ['bacoor','imus','dasmariñas'])) {
    $delivery_fee = 1500;
} elseif ($province === 'rizal' && in_array($city, ['tanay','jala-jala','pililla','baras'])) {
    $delivery_fee = 2000;
} elseif ($province === 'cavite' && in_array($city, ['tagaytay','silang','amadeo'])) {
    $delivery_fee = 2500;
} elseif ($province === 'laguna' && in_array($city, ['cabuyao','calamba','los baños'])) {
    $delivery_fee = 1500;
}

$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Your Cart</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
/* ============================= */
/*        GLOBAL STYLES          */
/* ============================= */

header { background: white; padding: 1rem 5%; display: flex; justify-content: space-between; align-items: center; color: #333; }


* { margin:0; padding:0; box-sizing:border-box; font-family:'Inter',sans-serif; }
body {
  background-image: url(bg.png);
  background-size: cover;
  color: #333;
  line-height: 1.6;
  display: flex;
  justify-content: center;
  align-items: flex-start;
  min-height: 100vh;
  padding: 40px 5vw;
  font-family: "Poppins", sans-serif;
}

.cart-wrapper { width:100%; max-width:900px; }
h1 { text-align:center; margin-bottom:20px; font-size:2rem; color: white; font-weight:700; }

/* ============================= */
/*       BUTTON STYLES            */
/* ============================= */
button, a.back-btn, .remove-btn, .checkout-btn, .modal-buttons button {
    background:black;
    color:white;
    padding:10px 18px;
    border-radius:8px;
    text-decoration:none;
    font-weight:600;
    border:1px solid transparent;
    cursor:pointer;
    transition:all 0.3s ease;
    display:inline-block;
    text-align:center;
}
button:hover, a.back-btn:hover, .remove-btn:hover, .checkout-btn:hover, .modal-buttons button:hover {
    background:white;
    color:black;
    border:1px solid black;
}
.checkout-btn.disabled { background:#ccc; color:#666; cursor:not-allowed; pointer-events:none; }

/* ============================= */
/*       BACK BUTTON SPECIFIC     */
/* ============================= */
a.back-btn {
    padding:12px 24px;
    font-size:1rem;
    margin-bottom:20px;
}

/* ============================= */
/*        CART STYLES            */
/* ============================= */
.cart-container { background:#fff; border-radius:14px; padding:30px; box-shadow:0 6px 20px rgba(0,0,0,0.08); }
.cart-item { display:flex; align-items:center; justify-content:space-between; border-bottom:1px solid #e5e7eb; padding:20px 0; }
.cart-item:last-child { border-bottom:none; }
.cart-item img { width:100px; height:100px; object-fit:cover; border-radius:10px; }
.cart-details { flex:2; padding:0 20px; }
.cart-details h3 { font-size:1.1rem; color:#111827; margin-bottom:6px; }
.cart-details p { margin-bottom:5px; color:#4b5563; font-size:0.95rem; }
.quantity-controls { display:flex; align-items:center; gap:10px; margin-top:8px; }
.quantity-controls button { background:black; color:white; padding:6px 12px; border-radius:6px; cursor:pointer; font-weight:600; font-size:1rem; transition:all 0.3s ease; }
.quantity-controls button:hover { background:white; color:black; border:1px solid black; }
.cart-actions { display:flex; flex-direction:column; gap:10px; }
.cart-summary { text-align:right; margin-top:25px; font-size:1.2rem; font-weight:600; color:#111827; }
.empty-cart { text-align:center; font-size:1.2rem; padding:60px 20px; color:#6b7280; }

/* ============================= */
/*        MODAL STYLES           */
/* ============================= */
.modal { display:none; position:fixed; z-index:10000; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center; }
.modal.active { display:flex; }
.modal-content { background:#fff; padding:30px; border-radius:14px; text-align:center; max-width:400px; width:90%; box-shadow:0 10px 25px rgba(0,0,0,0.15); }
.modal-content h2 { margin-bottom:20px; color:#111827; font-size:1.2rem; }
.modal-buttons { display:flex; justify-content:center; gap:20px; }

/* ============================= */
/*      FLASH MESSAGE            */
/* ============================= */
.flash-message { position:fixed; top:20px; right:20px; background:#dc2626; color:#fff; padding:12px 18px; border-radius:8px; font-weight:500; box-shadow:0 4px 12px rgba(0,0,0,0.15); opacity:0; transition:opacity 0.4s ease, transform 0.4s ease; transform:translateY(-20px); z-index:99999; }
.flash-message.show { opacity:1; transform:translateY(0); }

/* ============================= */
/*      PAYMENT PREVIEW          */
/* ============================= */
#paymentDetails label { font-weight:600; color:#111827; }
#proofPreview { margin-top:15px; text-align:center; }
#proofPreview img, #proofPreview iframe { border:2px solid #e5e7eb; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.15); max-width:100%; object-fit:contain; }
#proofPreview iframe { height:400px; }
#proofPreview p { color:#dc2626; font-size:0.9rem; font-weight:500; margin-top:8px; }

/* ============================= */
/*        RESPONSIVE             */
/* ============================= */
@media (max-width:768px) {
    .cart-item { flex-direction:column; text-align:center; }
    .cart-details { padding:15px 0; }
    .cart-actions { flex-direction:row; justify-content:center; }
    .cart-summary { text-align:center; }
    a.back-btn { padding:14px 28px; font-size:1.1rem; }
}

@media (max-width: 768px) {
  body {
    padding: 20px 10px;
  }

  .cart-wrapper {
    max-width: 100%;
  }

  .cart-container {
    padding: 20px;
  }

  .cart-item {
    flex-direction: column;
    align-items: center;
    text-align: center;
  }

  .cart-item img {
    width: 100%;
    max-width: 220px;
    height: auto;
  }

  .cart-details {
    padding: 10px 0;
  }

  .quantity-controls {
    justify-content: center;
  }

  .cart-actions {
    flex-direction: row;
    justify-content: center;
    gap: 12px;
    margin-top: 10px;
  }

  .cart-summary {
    font-size: 1rem;
    text-align: center;
  }

  a.back-btn {
    font-size: 1rem;
    padding: 10px 16px;
    width: 100%;
    display: block;
    text-align: center;
  }

  .modal-content {
    padding: 20px;
    width: 90%;
  }

  .modal-buttons {
    flex-direction: column;
    gap: 10px;
  }

  .modal-buttons button {
    width: 100%;
  }

  #paymentDetails label {
    font-size: 0.95rem;
  }

  #proofPreview iframe {
    height: 300px;
  }
}

</style>
</head>
<body>

<!-- Flash Message -->
<?php if (isset($_SESSION['flash'])): ?>
    <div id="flashMessage" class="flash-message show"><?= htmlspecialchars($_SESSION['flash']) ?></div>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<div class="cart-wrapper">
    <h1>Your Cart</h1>
    <a href="shop.php" class="back-btn">← Back to Shopping</a>

    <div class="cart-container">
    <?php if (count($cart_items) > 0): ?>
        <?php
        $grand_total = 0;
        foreach ($cart_items as $item):
            $total = $item['price'] * $item['quantity'];
            $grand_total += $total;
        ?>
        <div class="cart-item" data-id="<?= $item['id'] ?>" data-stock="<?= $item['stock'] ?>" data-price="<?= $item['price'] ?>">
            <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['product_name']) ?>">
            <div class="cart-details">
                <h3><?= htmlspecialchars($item['product_name']) ?></h3>
                <p><strong>Price:</strong> ₱<?= number_format($item['price'],2) ?></p>
                <p><strong>Stock Available:</strong> <?= $item['stock'] ?></p>
                <p><strong>Total:</strong> ₱<span class="item-total"><?= number_format($total,2) ?></span></p>
                <div class="quantity-controls">
                    <button class="decrease-btn">−</button>
                    <span class="quantity-display"><?= $item['quantity'] ?></span>
                    <button class="increase-btn">+</button>
                </div>
            </div>
            <div class="cart-actions">
                <button class="remove-btn" onclick="showModal(<?= $item['id'] ?>)">Remove</button>
            </div>
        </div>
        <?php endforeach; ?>

<div class="cart-summary">
    <p>Subtotal: ₱<span id="subTotal"><?= number_format($grand_total,2) ?></span></p>
    <p>Delivery Fee: ₱<?= number_format($delivery_fee,2) ?></p>
    <hr style="margin:10px 0;">
    <strong>
        Total Payable: ₱
        <span id="grandTotal">
            <?= number_format($grand_total + $delivery_fee,2) ?>
        </span>
    </strong>
    <br><br>
    <button class="checkout-btn" id="checkoutBtn">Proceed to Checkout</button>
</div>
    <?php else: ?>
        <div class="empty-cart">Your cart is empty.</div>
    <?php endif; ?>
    </div>
</div>

<!-- Remove Confirmation Modal -->
<div class="modal" id="confirmModal">
    <div class="modal-content">
        <h2>Are you sure you want to remove this item?</h2>
        <div class="modal-buttons">
            <button class="cancel-btn" onclick="closeModal()">Cancel</button>
            <button class="confirm-btn" id="confirmRemoveBtn">Remove</button>
        </div>
    </div>
</div>

<!-- Checkout Modal -->
<div class="modal" id="checkoutModal">
    <div class="modal-content">
        <h2>Select Payment Method</h2>
        <form id="checkoutForm" action="checkout.php" method="POST" enctype="multipart/form-data">
            <div class="payment-options" style="text-align:left; margin:20px 0;">
                <label><input type="radio" name="payment_method" value="gcash" required> GCash</label><br>
                <label><input type="radio" name="payment_method" value="bdo"> Bank Transfer</label><br>
                <label><input type="radio" name="payment_method" value="cod"> Cash on Delivery</label>
            </div>

            <div id="paymentDetails" style="text-align:left; font-size:0.95rem; color:#374151; margin-top:10px; display:none;">
                <div id="accountInfo"></div>
                <label style="display:block; text-align:left; margin-top:15px;">
                    Upload Proof of Payment (image/pdf)
                    <input type="file" name="proof" id="proofInput" accept="image/*,application/pdf" required style="margin-top:5px;">
                </label>
                <div id="proofPreview"></div>
            </div>

            <div class="modal-buttons" style="margin-top:20px;">
                <button type="button" onclick="closeCheckoutModal()">Cancel</button>
                <button type="button" onclick="confirmProof()">Confirm</button>
            </div>
        </form>
    </div>
</div>

<!-- Confirm Proof Modal -->
<div class="modal" id="proofConfirmModal">
  <div class="modal-content">
    <h2>Are you sure you uploaded the correct proof of payment?</h2>
    <div class="modal-buttons">
      <button onclick="closeProofConfirmModal()">Cancel</button>
      <button onclick="submitCheckoutForm()">Yes, Submit</button>
    </div>
  </div>
</div>

<script>
    
const DELIVERY_FEE = <?= $delivery_fee ?>;
// Flash message
const flashEl = document.getElementById('flashMessage');
if (flashEl) setTimeout(()=>{ flashEl.classList.remove('show'); }, 3000);

// Remove modal
let modal = document.getElementById('confirmModal');
let confirmBtn = document.getElementById('confirmRemoveBtn');
let removeUrl = '';
function showModal(itemId){ removeUrl='remove_from_cart.php?id='+itemId; modal.classList.add('active'); }
function closeModal(){ modal.classList.remove('active'); }
confirmBtn.addEventListener('click', ()=>window.location.href=removeUrl);

// Quantity logic
document.querySelectorAll('.cart-item').forEach(item => {
    let qtyDisplay = item.querySelector('.quantity-display');
    let itemTotalEl = item.querySelector('.item-total');
    let price = parseFloat(item.dataset.price);
    let stock = parseInt(item.dataset.stock);
    let cartId = item.dataset.id;
    item.querySelector('.increase-btn').addEventListener('click',()=>updateQty(item,qtyDisplay,itemTotalEl,1,price,stock,cartId));
    item.querySelector('.decrease-btn').addEventListener('click',()=>updateQty(item,qtyDisplay,itemTotalEl,-1,price,stock,cartId));
});

function updateQty(item, qtyDisplay, itemTotalEl, change, price, stock, cartId){
    let qty=parseInt(qtyDisplay.textContent)+change;
    if(qty<1) return;
    if(qty>stock){ qty=stock; showFlash("⚠️ Not enough stock!"); }
    qtyDisplay.textContent=qty;
    itemTotalEl.textContent=(qty*price).toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2});
    updateGrandTotal(); checkStockLimits(); saveToDatabase(cartId,qty);
}

function updateGrandTotal(){
    let subtotal = 0;
    document.querySelectorAll('.item-total').forEach(el=>{
        subtotal += parseFloat(el.textContent.replace(/,/g,''));
    });

    document.getElementById('subTotal').textContent =
        subtotal.toLocaleString('en-PH',{minimumFractionDigits:2});

    let grandTotal = subtotal + DELIVERY_FEE;

    document.getElementById('grandTotal').textContent =
        grandTotal.toLocaleString('en-PH',{minimumFractionDigits:2});
}

function checkStockLimits(){
    let disable=false;
    document.querySelectorAll('.cart-item').forEach(item=>{
        let qty=parseInt(item.querySelector('.quantity-display').textContent);
        let stock=parseInt(item.dataset.stock);
        if(qty>stock) disable=true;
    });
    let btn=document.getElementById('checkoutBtn');
    disable?btn.classList.add('disabled'):btn.classList.remove('disabled');
}

function saveToDatabase(cartId,qty){
    fetch('update_cart.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'cart_id='+cartId+'&quantity='+qty})
    .then(res=>res.json()).then(data=>{ if(data.status!=='success') showFlash("❌ Failed to update cart!"); })
    .catch(()=>showFlash("❌ Error updating cart!"));
}

checkStockLimits();

// Checkout
document.getElementById('checkoutBtn').addEventListener('click',()=>{
    if(!document.getElementById('checkoutBtn').classList.contains('disabled')) document.getElementById('checkoutModal').classList.add('active');
});
function closeCheckoutModal(){ document.getElementById('checkoutModal').classList.remove('active'); }

// Payment method logic
const paymentRadios=document.querySelectorAll('input[name="payment_method"]');
const paymentDetails=document.getElementById('paymentDetails');
const accountInfo=document.getElementById('accountInfo');

paymentRadios.forEach(radio => {
    radio.addEventListener('change', () => {
        if (radio.value === 'gcash') {
            accountInfo.innerHTML = `
                <br><strong>GCash Details</strong><br>
                Name: SHIELIE LAHOM<br>
                Numbers: 0928 433 6940 / 0998 355 4500
            `;
        } else if (radio.value === 'bdo') {
            accountInfo.innerHTML = `
                <br><strong>BDO Details</strong><br>
                Name: SHIELIE LAHOM<br>
                Account No: 0053-0038-03<br>
                Branch: Taytay Rizal
            `;
        } else if (radio.value === 'cod') {
            accountInfo.innerHTML = `
                <strong>COD Notice</strong>
                You are required to pay a ₱500.00 down payment via GCash before delivery.<br><br>
                <strong>GCash Details</strong><br>
                Name: SHIELIE LAHOM<br>
                Numbers: 0928 433 6940 / 0998 355 4500
            `;
        }
        paymentDetails.style.display = 'block';
    });
});

// Proof preview
document.getElementById('proofInput').addEventListener('change',e=>{
    const preview=document.getElementById('proofPreview'); preview.innerHTML='';
    const file=e.target.files[0];
    if(file){
        const ext=file.name.split('.').pop().toLowerCase();
        if(['jpg','jpeg','png'].includes(ext)){ const img=document.createElement('img'); img.src=URL.createObjectURL(file); preview.appendChild(img); }
        else if(ext==='pdf'){ const iframe=document.createElement('iframe'); iframe.src=URL.createObjectURL(file); preview.appendChild(iframe); }
    }
});

// Confirm Proof Modal
function confirmProof(){
    if(document.getElementById('proofInput').files.length===0){ showFlash("❌ Please upload proof!"); return; }
    document.getElementById('proofConfirmModal').classList.add('active');
}
function closeProofConfirmModal(){ document.getElementById('proofConfirmModal').classList.remove('active'); }
function submitCheckoutForm(){ document.getElementById('checkoutForm').submit(); }

// Flash helper
function showFlash(message,duration=3000){
    let flash=document.getElementById('flashMessage');
    if(!flash){ flash=document.createElement('div'); flash.id='flashMessage'; flash.className='flash-message'; document.body.appendChild(flash); }
    flash.textContent=message; flash.classList.add('show'); setTimeout(()=>{ flash.classList.remove('show'); },duration);
}
</script>
</body>
</html>