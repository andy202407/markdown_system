<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "存储测试\n";
echo "----------------\n";

$storage_path = 'markdown_files/';

echo "1. 检查当前目录\n";
echo "当前目录: " . getcwd() . "\n";
echo "脚本所在目录: " . __DIR__ . "\n";

echo "\n2. 检查PHP用户\n";
echo "当前PHP用户: " . get_current_user() . "\n";
echo "脚本所有者: " . fileowner(__FILE__) . "\n";

echo "\n3. 尝试创建目录\n";
if (!file_exists($storage_path)) {
    echo "目录不存在，尝试创建...\n";
    $result = mkdir($storage_path, 0777, true);
    if ($result) {
        echo "✅ 目录创建成功\n";
        chmod($storage_path, 0777);
        echo "✅ 权限设置为777\n";
    } else {
        echo "❌ 目录创建失败\n";
    }
} else {
    echo "目录已存在\n";
}

echo "\n4. 检查目录权限\n";
echo "目录权限: " . substr(sprintf('%o', fileperms($storage_path)), -4) . "\n";
echo "目录可写: " . (is_writable($storage_path) ? "是" : "否") . "\n";

echo "\n5. 尝试写入测试文件\n";
$test_file = $storage_path . 'test.md';
$content = "# 测试文件\n\n这是一个测试文件。";
$result = file_put_contents($test_file, $content);
if ($result !== false) {
    echo "✅ 文件写入成功\n";
    echo "文件大小: " . filesize($test_file) . " 字节\n";
    echo "文件内容:\n" . file_get_contents($test_file) . "\n";
} else {
    echo "❌ 文件写入失败\n";
}

echo "\n6. 检查目录内容\n";
$files = scandir($storage_path);
echo "目录内容:\n";
foreach ($files as $file) {
    if ($file != '.' && $file != '..') {
        echo "- $file\n";
    }
}
?>
