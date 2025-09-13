<?php
// 数据库连接测试脚本
require_once 'env.php';

echo "测试数据库连接...\n";
echo "DB_HOST: " . env('DB_HOST', 'localhost') . "\n";
echo "DB_NAME: " . env('DB_NAME', 'markdown_system') . "\n";
echo "DB_USERNAME: " . env('DB_USERNAME', 'markdown_system') . "\n";
echo "DB_PASSWORD: " . (env('DB_PASSWORD', '') ? '[已设置]' : '[未设置]') . "\n";

try {
    $dsn = "mysql:host=" . env('DB_HOST', 'localhost') . ";dbname=" . env('DB_NAME', 'markdown_system') . ";charset=" . env('DB_CHARSET', 'utf8mb4');
    $pdo = new PDO($dsn, env('DB_USERNAME', 'markdown_system'), env('DB_PASSWORD', ''), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    echo "✅ 数据库连接成功！\n";
} catch (PDOException $e) {
    echo "❌ 数据库连接失败: " . $e->getMessage() . "\n";
    echo "\n建议解决方案:\n";
    echo "1. 检查数据库用户是否存在\n";
    echo "2. 检查密码是否正确\n";
    echo "3. 尝试使用 root 用户\n";
    echo "4. 检查数据库服务是否运行\n";
}
