<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';
$mail = get_setting('contact_email', CONTACT_EMAIL);
pageHead('Privacy policy — ' . APP_NAME, 'How Motor Parts Market collects, uses and protects your personal information (POPIA).', ['canonical' => APP_URL . '/privacy.php']);
simpleTop();
?>
<main class="page"><div class="prose glass">
  <h1>Privacy policy</h1>
  <p>Last updated: <?= date('j F Y', filemtime(__FILE__)) ?></p>
  <p><?= e(APP_NAME) ?> ("we") sells vehicle lights in South Africa through motorpartsmarket.co.za. This page explains what personal information we collect and what we do with it, in line with the Protection of Personal Information Act (POPIA).</p>
  <h2>What we collect</h2>
  <ul>
    <li><strong>Enquiries and orders:</strong> your name, cell number, optional email, town or suburb, your vehicle details and your message.</li>
    <li><strong>Chat messages:</strong> what you type into the parts assistant, so we can see which parts people are looking for.</li>
    <li><strong>Technical data:</strong> your IP address and a session cookie that keeps the chat working.</li>
    <li><strong>Messaging apps:</strong> if you message us on WhatsApp, we receive your number and your messages.</li>
  </ul>
  <h2>Why we use it</h2>
  <p>To answer your enquiry, confirm that a part fits your vehicle, quote a price and delivery cost, deliver your order, and improve our stock. We do not sell your information.</p>
  <h2>Who we share it with</h2>
  <p>Only the parties needed to help you: the courier that delivers your order, our hosting provider, and — when the assistant needs help understanding a message — an AI service (Groq) that receives the text of that message only, never your name or number.</p>
  <h2>Cookies and analytics</h2>
  <p>We use one essential session cookie. If we switch on Google Analytics or the Meta Pixel, they measure visits and advertising results.</p>
  <h2>How long we keep it</h2>
  <p>Enquiries and orders are kept for as long as needed for the sale and for our tax and record-keeping duties. Chat logs are deleted after 12 months.</p>
  <h2>Your rights</h2>
  <p>You may ask us for a copy of your information, to correct it, or to delete it. Email <a href="mailto:<?= e($mail) ?>"><?= e($mail) ?></a> and we will respond within 30 days. You may also complain to the Information Regulator (South Africa).</p>
</div></main>
<?php
simpleFooter();
pageFoot();
