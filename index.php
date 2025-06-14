<?php

// 緩存文件位置
define('CACHE_FILE', __DIR__ . '/session_token_cache.json');
define('CACHE_TTL', 10800); // 3 小時 (10800 秒)

// 獲取 Session Token，帶有緩存功能
function getSessionToken() {
    // 檢查緩存文件是否存在且未過期
    if (file_exists(CACHE_FILE)) {
        $cache = json_decode(file_get_contents(CACHE_FILE), true);
        if (isset($cache['session_token']) && isset($cache['timestamp'])) {
            if (time() - $cache['timestamp'] < CACHE_TTL) {
                return $cache['session_token'];
            }
        }
    }

    // 緩存無效，重新請求 session_token
    $authUrl = 'i.php';
    $authResponse = file_get_contents($authUrl);
    if ($authResponse === false) {
        die('Failed to get session token.');
    }

    $authData = json_decode($authResponse, true);
    if (!isset($authData['data']['session_token'])) {
        die('Failed to extract session token.');
    }

    $sessionToken = $authData['data']['session_token'];

    // 保存到緩存文件
    file_put_contents(CACHE_FILE, json_encode([
        'session_token' => $sessionToken,
        'timestamp' => time()
    ]));

    return $sessionToken;
}

// 取得緩存的 session_token
$sessionToken = getSessionToken();

// 獲取 URL 中的 ch 參數
$channelId = isset($_GET['ch']) ? intval($_GET['ch']) : 1;

if ($channelId <= 0) {
    die('Invalid channel ID provided.');
}

// 設置 API 請求
$url = 'https://uapisfm.tvbanywhere.com.sg/video/channel/checkout';
$headers = [
    'accept: application/json, text/plain, */*',
    'accept-language: zh-CN,zh;q=0.9,en;q=0.8,en-GB;q=0.7,en-US;q=0.6',
    'authorization: Bearer ' . $sessionToken,
    'content-type: application/json;charset=UTF-8',
    'origin: https://www.tvbanywhere.com',
    'referer: https://www.tvbanywhere.com/',
    'user-agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0'
];

$data = json_encode([
    'live_channel_id' => $channelId,
    'platform' => 'webtv',
    'country' => 'JP',
    'start_time' => time(),
    'stop_time' => null,
    'quality' => 'auto',
    'broadcast' => 'webtv'
]);

// 初始化 cURL
$curl = curl_init($url);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

// 執行 cURL 請求
$response = curl_exec($curl);

if (curl_errno($curl)) {
    die('CURL Error: ' . curl_error($curl));
}

curl_close($curl);

// 輸出最終 JSON 結果
header('Content-Type: application/json');
echo $response;
