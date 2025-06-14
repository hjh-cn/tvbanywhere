#!/bin/bash

URL="index.php?ch=69"

# 递归请求数据，直到域名变为 sg01-live-cfd.tvbanywhere.com
domain=""
final_policy=""
final_signature=""
final_key_pair_id=""

# URL 编码函数，用于转义特殊字符
urlencode() {
    local str="$1"
    # 将字符转义为 URL 编码
    echo -n "$str" | jq -sRr @uri
}

while true; do
    data=$(curl -s "$URL")
    domain=$(echo "$data" | jq -r '.video_paths[0].url' | awk -F/ '{print $3}')
    
    # 提取 cookies
    policy=$(echo "$data" | jq -r '.cookies[] | select(.name=="CloudFront-Policy") | .value')
    signature=$(echo "$data" | jq -r '.cookies[] | select(.name=="CloudFront-Signature") | .value')
    key_pair_id=$(echo "$data" | jq -r '.cookies[] | select(.name=="CloudFront-Key-Pair-Id") | .value')

    # 检查是否正确提取了 cookies
    if [ -z "$policy" ] || [ -z "$signature" ] || [ -z "$key_pair_id" ]; then
        echo "Error: Missing one or more required cookies."
        echo "CloudFront-Policy: $policy"
        echo "CloudFront-Signature: $signature"
        echo "CloudFront-Key-Pair-Id: $key_pair_id"
        exit 1
    fi
    
    # 显示当前域名和 Cookies
    echo "Detected domain: $domain"
    echo "CloudFront-Policy: $policy"
    echo "CloudFront-Signature: $signature"
    echo "CloudFront-Key-Pair-Id: $key_pair_id"
    
    # 如果域名是目标域名，则退出循环，并保存最后的 Cookies
    if [ "$domain" == "sg01-live-cfd.tvbanywhere.com" ]; then
        final_policy="$policy"
        final_signature="$signature"
        final_key_pair_id="$key_pair_id"
        break
    fi
    
    echo "Domain is not correct, re-requesting data..."
    sleep 2  # 避免请求过于频繁
done

# 确保使用的是 sg01-live-cfd.tvbanywhere.com 对应的 cookies
echo "Final domain and cookies will be used for Nginx configuration."
echo "Using domain: $domain"
echo "CloudFront-Policy: $final_policy"
echo "CloudFront-Signature: $final_signature"
echo "CloudFront-Key-Pair-Id: $final_key_pair_id"

# 对 cookies 进行 URL 编码
encoded_policy=$(urlencode "$final_policy")
encoded_signature=$(urlencode "$final_signature")
encoded_key_pair_id=$(urlencode "$final_key_pair_id")

# 填入指定路径的 Nginx 配置文件
echo "Updating Nginx configuration..."
cat <<EOF > /location
location ^~ / {
    proxy_pass https://sg01-live-cfd.tvbanywhere.com;
    proxy_set_header Cookie "CloudFront-Policy=$encoded_policy";
    proxy_set_header Cookie "CloudFront-Signature=$encoded_signature";
    proxy_set_header Cookie "CloudFront-Key-Pair-Id=$encoded_key_pair_id";
    proxy_http_version 1.1;
    proxy_ssl_server_name on;
    proxy_ssl_name sg01-live-cfd.tvbanywhere.com;
    proxy_set_header Host sg01-live-cfd.tvbanywhere.com;
    add_header Cache-Control no-cache;
}
EOF

echo "Nginx 配置已更新"

# 重启 Nginx
echo "Reloading Nginx service..."
nginx -s reload
echo "Nginx reloaded successfully."
