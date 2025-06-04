<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Ejoi8\PaymentGateway\Services\PaymentService;

Route::get('/', function () {
    return view('welcome');
});

/**
 * Route to initiate a payment.
 * If the gateway is 'manual', it will redirect to a form for proof upload.
 * Other gateways will redirect to their respective payment URLs.
 */
Route::get('/test/{gateway?}', function (Request $request, $gateway = 'chipin') {
    $paymentService = new PaymentService();
    $allowedGateways = ['chipin', 'toyyibpay', 'manual'];
    if (!in_array($gateway, $allowedGateways)) {
        $gateway = 'chipin'; // Defaulting if invalid
    }

    $paymentData = [
        'amount' => 115.00,
        'currency' => 'MYR',
        'gateway' => $gateway,
        'description' => 'Multiple Items Purchase',
        'customer_email' => $request->input('customer_email', 'customer@example.com'),
        'customer_name' => $request->input('customer_name', 'John Doe'),
        'customer_phone' => $request->input('customer_phone', '+60123456789'),
        'order_id' => 'ORDER-' . uniqid(),
        // 'language' => $request->input('language', 'en'),
        'external_reference_id' => 'REF-' . uniqid(),
        'reference_type' => 'order',
        'products' => [
            ['name' => 'Premium T-Shirt', 'price' => 45.00, 'quantity' => 2],
            ['name' => 'Coffee Mug', 'price' => 25.00, 'quantity' => 1],
        ]
    ];

    $result = $paymentService->createPayment($paymentData);

    if (!$result['success']) {
        return back()->withErrors(['payment' => $result['message'] ?? 'Payment initiation failed.'])->withInput();
    }

    if ($result['payment']->gateway === 'manual') {
        return redirect()->route('payment-gateway.manual.upload', ['payment' => $result['payment']]);
    }

    return redirect($result['payment_url']);
})->name('test.payment');


Route::get('/form', function () {
    return '
        <!DOCTYPE html>
        <html>
        <head>
            <title>Payment Test</title>
        </head>
        <body>
            <h1>Manual Payment Upload</h1>
            <form action="/form2" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="_token" value="' . csrf_token() . '">

                <label>Name:</label><br>
                <input type="text" name="customer_name" value="John Doe"><br><br>

                <label>Email:</label><br>
                <input type="email" name="customer_email" value="customer@example.com"><br><br>

                <label>Phone:</label><br>
                <input type="text" name="customer_phone" value="+60123456789"><br><br>

                <label>Proof of Payment (jpg/png):</label><br>
                <input type="file" name="proof_file"><br><br>

                <button type="submit">Submit Payment</button>
            </form>
        </body>
        </html>
    ';
});

Route::post('/form2', function (Request $request) {
    $request->validate([
        'proof_file' => 'required|file|mimes:jpg,jpeg,png|max:2048',
    ]);

    $path = $request->file('proof_file')->store('proofs', 'public');

    $paymentService = new PaymentService();
    $result = $paymentService->createPayment([
        'amount' => 115.00,
        'currency' => 'MYR',
        'gateway' => 'manual',
        'proof_file' => $request->file('proof_file'),
        'description' => 'Multiple Items Purchase',
        'customer_email' => $request->input('customer_email'),
        'customer_name' => $request->input('customer_name'),
        'customer_phone' => $request->input('customer_phone'),
        'order_id' => 'ORDER-12345',
        'language' => 'en',
        'external_reference_id' => '1',
        'reference_type' => 'order',
        'products' => [
            ['name' => 'Premium T-Shirt', 'price' => 45.00, 'quantity' => 2],
            ['name' => 'Coffee Mug', 'price' => 25.00, 'quantity' => 1],
        ]
    ]);
    // dd($result);

    if ($result['success']) {
        // Handling redirection based on response
        if (isset($result['redirect_url'])) {
            // If proof was uploaded successfully, redirect to thank you page
            return redirect($result['redirect_url']);
        } elseif (isset($result['payment_url'])) {
            // If proof upload is still required, redirect to upload page
            return redirect($result['payment_url']);
        }

        // Fallback redirect
        return redirect()->route('payment.success');
    }
});
