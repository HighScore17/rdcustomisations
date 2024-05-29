<?php
if( !class_exists( 'WoocommerceCheckoutRateLimitier' ) ) {
  ?>
  <div class="error">
    <p><strong style="font-weight: 700">Checkout Rate Limitier isn't active</strong> please verify that memcached is installed to protect our payment methods.</p>
  </div>
  <?php
}