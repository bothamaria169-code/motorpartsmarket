<?php
declare(strict_types=1);

require __DIR__ . '/_boot.php';

if (!rateLimit('enquiry', 5, 600)) jsonResponse(['ok' => false, 'error' => 'You\'ve sent a few enquiries already. Please wait a bit, or WhatsApp us.'], 429);

$name = postText('name', 120);
$phone = preg_replace('/[^\d+ ]/', '', postText('phone', 20));
$email = postText('email', 255);
if ($name === '' || strlen(preg_replace('/\D/', '', $phone)) < 9) jsonResponse(['ok' => false, 'error' => 'Please give your name and a valid cell number.'], 422);
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $email = '';
if (empty($_POST['consent'])) jsonResponse(['ok' => false, 'error' => 'Please tick the box so we may contact you.'], 422);

$product = ($pid = (int) ($_POST['product_id'] ?? 0)) ? productById($pid) : null;
$vehicle = postText('vehicle', 160);
$area = postText('area', 120);
$message = postText('message', 1000);

$db = getDB();
$db->prepare('INSERT INTO enquiries (product_id, customer_name, phone, email, area, vehicle, message, source, ip) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')
   ->execute([$product['id'] ?? null, $name, $phone, $email ?: null, $area ?: null, $vehicle ?: null, $message ?: null, 'web', clientIp()]);
$id = (int) $db->lastInsertId();
$ref = 'MPM-' . str_pad((string) $id, 5, '0', STR_PAD_LEFT);

$lines = array_filter([
    "New enquiry {$ref}",
    $product ? "Part: {$product['title']} (" . years($product['year_from'] ? (int) $product['year_from'] : null, $product['year_to'] ? (int) $product['year_to'] : null) . ')' : 'Part: general enquiry',
    "Name: {$name}", "Phone: {$phone}",
    $vehicle ? "Vehicle: {$vehicle}" : '', $area ? "Area: {$area}" : '', $message ? "Message: {$message}" : '',
]);
sendMail(get_setting('contact_email', CONTACT_EMAIL), "New enquiry {$ref} — {$name}", implode("\n", $lines) . "\n\n" . APP_URL . '/admin/enquiries.php', $email ?: null);

$waText = "Hi, I just sent enquiry {$ref}." . ($product ? " I'm interested in: {$product['title']}." : '') . ($vehicle ? " My vehicle: {$vehicle}." : '');
jsonResponse(['ok' => true, 'ref' => $ref, 'whatsapp' => whatsappLink($waText)]);
