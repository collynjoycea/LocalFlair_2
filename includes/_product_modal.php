<?php
// Shared product modal - One file to rule them all!
?>


<style>
/* Global Modal Fix */
#productModal .modal-content {
    border-radius: 24px !important;
    border: none !important;
    overflow: hidden;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
    background-color: #fff;
}

/* IMAGE SIDE: Lock the height here */
.modal-img-container {
    background-color: #013220;
    display: flex;
    align-items: center;
    justify-content: center;
    /* Eto ang magla-lock ng height para laging pantay */
    height: 600px !important; 
    overflow: hidden;
}

.modal-img-container img {
    width: 100%;
    height: 100%;
    /* Eto ang magic: i-crop nya yung portrait image para mag-fit */
    object-fit: cover !important; 
    object-position: center;
}

/* DETAILS SIDE: Lock the height to match the image */
.modal-details-side {
    padding: 35px;
    position: relative;
    height: 600px !important; /* Pantay sa image side */
    display: flex;
    flex-direction: column;
    overflow-y: auto; /* Kung mahaba ang description, dito lang mag-scoscroll */
}

/* Buttons and Quantity at the bottom */
.modal-actions-footer {
    margin-top: auto; /* Push everything below to the bottom */
    padding-top: 20px;
}

#productModal .btn-close {
    background-color: #f1f5f9;
    border-radius: 50%;
    opacity: 1;
    padding: 10px;
    position: absolute;
    top: 20px;
    right: 20px;
    z-index: 10;
}
</style>
<div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content" style="border-radius:24px; overflow:hidden; border:none; background:#fff;">
      <div class="modal-body p-0">
        <div class="row g-0">
          
          <div class="col-md-6" style="background-color: #013220; display: flex; align-items: center; justify-content: center; min-height: 450px;">
            <img id="pmImage" src="" alt="" style="width:100%; height:100%; object-fit:cover;">
          </div>
          
          <div class="col-md-6 modal-details" style="padding: 35px; position: relative;">
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="position: absolute; top: 20px; right: 20px; z-index: 10;"></button>

            <div class="breadcrumb-text mb-2">
              <span id="pmBreadcrumb" style="font-size: 11px; color: #e95a24; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px;"></span> 
              <span id="pmStatusBadge" style="font-size: 11px; font-weight: 700; margin-left: 5px;">› AVAILABLE</span>
            </div>

            <h1 class="fw-bold mb-1" id="pmTitle" style="color: #1e293b; font-size: 28px;"></h1>
            <div class="fw-bold mb-4" id="pmPrice" style="font-size: 24px; color: #e95a24;"></div>

            <div class="mb-3">
              <label class="fw-bold d-block mb-0" style="color: #1e293b; font-size: 14px;">Origin</label>
              <span id="pmOrigin" class="text-muted small"></span>
            </div>

            <div class="mb-3">
              <label class="fw-bold d-block mb-0" style="color: #1e293b; font-size: 14px;">Product Details</label>
              <div class="text-muted small">
                Net Content: <span id="pmContent"></span><br>
                Packaging: <span id="pmPackaging"></span>
              </div>
            </div>

            <div class="mb-4">
              <label class="fw-bold d-block mb-1" style="color: #1e293b; font-size: 14px;">Description</label>
              <p id="pmDescription" class="text-muted small mb-0" style="line-height: 1.5;"></p>
            </div>

            <label class="fw-bold d-block mb-2" style="color: #1e293b; font-size: 14px;">Quantity</label>
            <div class="d-flex align-items-center bg-light rounded-3 p-1 mb-4" style="width: fit-content;">
                <button type="button" class="btn btn-link text-dark px-3 py-1 text-decoration-none fw-bold" onclick="pmQty(-1)">−</button>
                <input id="pmQty" value="0" readonly class="form-control border-0 bg-transparent text-center fw-bold" style="width:50px; box-shadow:none;">
                <button type="button" class="btn btn-link text-dark px-3 py-1 text-decoration-none fw-bold" onclick="pmQty(1)">+</button>
            </div>
            <div class="d-flex gap-3 mt-4">
             <form id="pmBuyForm" method="POST" action="place-order.php" style="width: 50%; margin: 0;">
            <input type="hidden" name="selected_items[]" id="pmBuyId">
            <input type="hidden" name="buy_now_qty" id="pmBuyQty">
                <button type="submit" 
                        class="btn fw-bold" 
                        style="background-color: #ead9bd !important; 
                               color: #2d1b10 !important; 
                               border: none !important; 
                               border-radius: 12px !important; 
                               width: 100% !important; 
                               height: 55px !important; 
                               font-size: 16px !important;
                               display: flex;
                               align-items: center;
                               justify-content: center;
                               box-shadow: none !important;">
                  Buy Now
                </button>
              </form>
              
              <button id="pmCartBtn" 
                      type="button" 
                      onclick="pmAddToCart()"
                      class="btn fw-bold" 
                      style="background-color: #ead9bd !important; 
                             color: #2d1b10 !important; 
                             border: none !important; 
                             border-radius: 12px !important; 
                             width: 50% !important; 
                             height: 55px !important; 
                             font-size: 16px !important;
                             display: flex;
                             align-items: center;
                             justify-content: center;
                             box-shadow: none !important;">
                Add to Cart
              </button>
            </div>

            <div class="mt-3" id="pmMsg" style="display:none;"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
let pmProductData = null;

function pmQty(delta){
  const input = document.getElementById('pmQty');
  let current = parseInt(input.value || '0', 10);
  let next = current + delta;
  if(next < 0) next = 0; 
  input.value = String(next);
}

function pmSetMsg(html, type){
  const el = document.getElementById('pmMsg');
  el.style.display = 'block';
  el.className = 'alert py-2 mb-2 ' + (type === 'ok' ? 'alert-success' : 'alert-danger');
  el.textContent = html;
}

async function pmAddToCart(){
  if(!pmProductData) return;
  const qty = parseInt(document.getElementById('pmQty').value || '0', 10);
  if(qty < 1){
    pmSetMsg('Please select a quantity first.', 'err');
    return;
  }
  const body = new URLSearchParams();
  body.set('id', pmProductData.product_id);
  body.set('qty', qty);
  const res = await fetch('add-to-cart.php', { method: 'POST', body });
  const text = (await res.text()).trim();
  if(text === 'success'){
    pmSetMsg('Added to cart.', 'ok');
    if(typeof refreshCartBadge === 'function') refreshCartBadge();
  } else {
    if(text === 'login_required'){ alert('Please login first.'); window.location = 'login.php'; return; }
    pmSetMsg(text || 'Error adding to cart.', 'err');
  }
}

document.getElementById('productModal')?.addEventListener('show.bs.modal', (event) => {
  const btn = event.relatedTarget;
  // Support both JSON string or direct parameters
  const rawData = btn.getAttribute('data-product');
  pmProductData = JSON.parse(rawData);
  const img = btn.getAttribute('data-image') || pmProductData.image_url;

  document.getElementById('pmImage').src = img;
  document.getElementById('pmTitle').textContent = pmProductData.product_name;
  document.getElementById('pmBreadcrumb').textContent = (pmProductData.category_name || 'LOCAL PRODUCT').toUpperCase();
  document.getElementById('pmPrice').textContent = '₱' + Number(pmProductData.price).toFixed(2);
  document.getElementById('pmOrigin').textContent = pmProductData.province_name || 'Philippines';
  document.getElementById('pmContent').textContent = pmProductData.net_content || 'N/A';
  document.getElementById('pmPackaging').textContent = pmProductData.packaging || 'N/A';
  document.getElementById('pmDescription').textContent = pmProductData.description || 'Premium local product.';
  
  document.getElementById('pmQty').value = '0';
  document.getElementById('pmBuyId').value = pmProductData.product_id;
  document.getElementById('pmMsg').style.display = 'none';

  const badge = document.getElementById('pmStatusBadge');
  const isAvailable = parseInt(pmProductData.stock) > 0;
  badge.textContent = isAvailable ? '› AVAILABLE' : '› OUT OF STOCK';
  badge.style.color = isAvailable ? '#28a745' : '#dc3545';
});

document.getElementById('pmBuyForm')?.addEventListener('submit', function(e) {
  const qty = parseInt(document.getElementById('pmQty').value || '0', 10);
  if(qty < 1){ alert('Please select a quantity first.'); e.preventDefault(); return; }
  document.getElementById('pmBuyQty').value = String(qty);
});
</script>