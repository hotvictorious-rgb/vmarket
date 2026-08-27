<?php

$ch = curl_init('http://127.0.0.1:8000/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$html = curl_exec($ch);
curl_close($ch);

if (str_contains($html, 'id="loginModal"')) {
    echo "✅ loginModal found in HTML\n";
} else {
    echo "❌ loginModal NOT found\n";
}

if (str_contains($html, 'name="email"') || str_contains($html, 'name="user_id"')) {
    echo "✅ Login email/username input field found\n";
} else {
    echo "❌ Login email/username field NOT found\n";
}

if (str_contains($html, 'name="password"')) {
    echo "✅ Login password input field found\n";
} else {
    echo "❌ Login password field NOT found\n";
}

if (str_contains($html, 'id="customerLoginBtn"') || str_contains($html, 'id="customerOtpLogin"')) {
    echo "✅ Customer Login submit button found\n";
} else {
    echo "❌ Customer Login button NOT found\n";
}

if (str_contains($html, 'data-bs-target="#registerModal"')) {
    echo "✅ Sign-up switch link found\n";
} else {
    echo "❌ Sign-up link NOT found\n";
}
