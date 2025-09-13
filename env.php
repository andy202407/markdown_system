<?php
/**
 * 环境变量加载器
 * 从 .env 文件加载配置
 */

function loadEnv($path = '.env') {
    if (!file_exists($path)) {
        return;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        // 跳过注释行
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // 解析 KEY=VALUE 格式
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // 移除引号
            if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                $value = substr($value, 1, -1);
            }
            
            // 设置环境变量（如果尚未设置）
            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $value;
                // putenv() 可能被禁用，只使用 $_ENV
            }
        }
    }
}

/**
 * 获取环境变量
 */
function env($key, $default = null) {
    $value = $_ENV[$key] ?? getenv($key);
    return $value !== false ? $value : $default;
}

// 自动加载环境变量
loadEnv();
