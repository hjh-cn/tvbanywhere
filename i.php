<?php

// 請求的 URL
$url = 'https://id-api.tvb.com/frontend/devices/login_by_user/v3';

// POST 數據
$postData = http_build_query([
    'app_version'        => '',
    'device_id'          => '',
    'device_language'    => '',
    'device_token'       => '',
    'device_type'        => '',
    'login_country_code' => '',
    'user_token'         => ''
]);

// 設置請求頭
$headers = [
    'authority: id-api.tvb.com',
    'Accept: */*',
    'Content-Type: application/x-www-form-urlencoded',
    'Accept-Encoding: gzip, deflate, br',
    'User-Agent: TVBAnywhere-Global/2.25.0 (iPhone; iOS 18.4; Scale/3.00)',
    'Accept-Language: zh-Hant-HK;q=1, yue-Hant-HK;q=0.9, zh-Hans-HK;q=0.8, en-HK;q=0.7'
];

// 初始化 cURL
$ch = curl_init();

// 設置 cURL 參數
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate, br');

// 執行請求並直接輸出原始數據
$response = curl_exec($ch);

if (curl_errno($ch)) {
    // 如果有錯誤，輸出錯誤訊息
    echo 'cURL Error: ' . curl_error($ch);
} else {
    // 設置響應頭，直接輸出原始返回數據
    header('Content-Type: application/json');
    echo $response;
}

// 關閉 cURL
curl_close($ch);
