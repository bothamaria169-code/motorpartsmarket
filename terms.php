<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';
$mail = get_setting('contact_email', CONTACT_EMAIL);
pageHead('Terms of sale — ' . APP_NAME, 'Terms for buying vehicle lights from Motor Parts Market: fitment, prices, delivery, returns.', ['canonical' => APP_URL . '/terms.php']);
simpleTop();
?>
<main class="page"><div class="prose glass">
  <h1>Terms of sale</h1>
  <p>Last updated: <?= date('j F Y', filemtime(__FILE__)) ?></p>
  <h2>1. How ordering works</h2>
  <p>Sending an enquiry is not yet an order. We first confirm with you that the part is still in stock, that it fits your vehicle, the price and the delivery cost. Your order is confirmed once you accept that quote and pay.</p>
  <h2>2. Fitment</h2>
  <p>Light shapes differ between model years and trim levels. The year ranges on the site are a guide. Give us your vehicle's make, model and year (and a photo of your current light if you can) so we can confirm fitment before you pay.</p>
  <h2>3. Photos and condition</h2>
  <p>The photos and videos show the actual stock. Where a part is used, we describe its condition and list every defect we know about.</p>
  <h2>4. Prices</h2>
  <p>Prices are in South African rand. Where the site says "Ask for price", we send the price with your quote. Delivery is quoted separately.</p>
  <h2>5. Delivery</h2>
  <p>We deliver across South Africa by courier. Delivery times are estimates from the courier. Check the parcel when it arrives and tell us about any damage within 48 hours, with photos.</p>
  <h2>6. Returns and refunds</h2>
  <p>If a part arrives damaged, is not as described, or does not fit the vehicle details you gave us, tell us within 7 days and we will exchange or refund it. Parts must come back uninstalled and in the condition you received them. Nothing here limits your rights under the Consumer Protection Act or the Electronic Communications and Transactions Act.</p>
  <h2>7. Contact</h2>
  <p>Questions or complaints: <a href="mailto:<?= e($mail) ?>"><?= e($mail) ?></a>.</p>
</div></main>
<?php
simpleFooter();
pageFoot();
