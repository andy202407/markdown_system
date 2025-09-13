<?php
// 数据库初始化脚本
// 运行此脚本来创建必要的数据库和表结构

// 加载配置文件
$config = require_once 'config.php';
$dbConfig = $config['database'];

try {
    // 连接MySQL服务器（不指定数据库）
    $dsn = "mysql:host={$dbConfig['host']};charset={$dbConfig['charset']}";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    
    echo "✅ 成功连接到MySQL服务器\n";
    
    // 创建数据库
    $dbname = $dbConfig['dbname'];
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ 数据库 '{$dbname}' 创建成功\n";
    
    // 选择数据库
    $pdo->exec("USE `{$dbname}`");
    
    // 创建数据库管理表
    $createDatabasesTable = "
    CREATE TABLE IF NOT EXISTS `databases` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(255) NOT NULL COMMENT '数据库名',
        `username` varchar(255) NOT NULL COMMENT '用户名',
        `password` varchar(255) NOT NULL COMMENT '密码',
        `capacity` decimal(10,2) DEFAULT 0.00 COMMENT '容量(GB)',
        `backup_status` enum('是','否') DEFAULT '否' COMMENT '备份状态',
        `location` varchar(255) DEFAULT '' COMMENT '数据库位置',
        `remark` text COMMENT '备注',
        `status` enum('online','offline') DEFAULT 'online' COMMENT '状态',
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
        PRIMARY KEY (`id`),
        UNIQUE KEY `name` (`name`),
        KEY `status` (`status`),
        KEY `created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='数据库管理表';
    ";
    
    $pdo->exec($createDatabasesTable);
    echo "✅ 数据表 'databases' 创建成功\n";
    
    // 创建Markdown文件表
    $createMarkdownTable = "
    CREATE TABLE IF NOT EXISTS `markdown_files` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `filename` varchar(255) NOT NULL COMMENT '文件名',
        `content` longtext NOT NULL COMMENT '文件内容',
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
        PRIMARY KEY (`id`),
        UNIQUE KEY `filename` (`filename`),
        KEY `updated_at` (`updated_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Markdown文件表';
    ";
    
    $pdo->exec($createMarkdownTable);
    echo "✅ 数据表 'markdown_files' 创建成功\n";
    
    // 创建用户表（可选，用于未来扩展）
    $createUsersTable = "
    CREATE TABLE IF NOT EXISTS `users` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `username` varchar(50) NOT NULL COMMENT '用户名',
        `email` varchar(100) NOT NULL COMMENT '邮箱',
        `password_hash` varchar(255) NOT NULL COMMENT '密码哈希',
        `role` enum('admin','user') DEFAULT 'user' COMMENT '角色',
        `status` enum('active','inactive') DEFAULT 'active' COMMENT '状态',
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
        PRIMARY KEY (`id`),
        UNIQUE KEY `username` (`username`),
        UNIQUE KEY `email` (`email`),
        KEY `status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户表';
    ";
    
    $pdo->exec($createUsersTable);
    echo "✅ 数据表 'users' 创建成功\n";
    
    // 插入示例数据
    $insertSampleData = "
    INSERT IGNORE INTO `databases` (`name`, `username`, `password`, `capacity`, `backup_status`, `location`, `remark`, `status`) VALUES
    ('markdown', 'admin', 'TdNmKy7cysMbd3RG', 10.50, '是', 'localhost:3306', '主要数据库', 'online'),
    ('test_db', 'test_user', 'test123456', 5.00, '否', '192.168.1.100:3306', '测试数据库', 'online'),
    ('backup_db', 'backup_user', 'backup789', 20.00, '是', 'backup.server.com:3306', '备份数据库', 'offline');
    ";
    
    $pdo->exec($insertSampleData);
    echo "✅ 示例数据插入成功\n";
    
    // 插入示例Markdown文件
    $insertSampleMarkdown = "
    INSERT IGNORE INTO `markdown_files` (`filename`, `content`) VALUES
    ('welcome.md', '# 欢迎使用Markdown编辑器

这是一个功能强大的Markdown编辑器，支持实时预览。

## 功能特性

- **实时预览**: 左侧编辑，右侧预览
- **语法高亮**: 支持代码语法高亮
- **文件管理**: 可以保存和加载文件
- **响应式设计**: 适配各种屏幕尺寸

## 示例代码

```javascript
function hello() {
    console.log(\"Hello, World!\");
}
```

## 表格示例

| 功能 | 状态 | 描述 |
|------|------|------|
| 实时预览 | ✅ | 支持 |
| 语法高亮 | ✅ | 支持 |
| 文件保存 | ✅ | 支持 |

## 引用

> 这是一个引用示例，可以用来突出重要信息。

## 列表

1. 第一项
2. 第二项
3. 第三项

- 无序列表项1
- 无序列表项2
- 无序列表项3

---

*享受使用Markdown编辑器的乐趣！*');
    ";
    
    $pdo->exec($insertSampleMarkdown);
    echo "✅ 示例Markdown文件插入成功\n";
    
    echo "\n🎉 数据库初始化完成！\n";
    echo "📊 数据库: {$dbname}\n";
    echo "📋 表结构:\n";
    echo "   - databases (数据库管理)\n";
    echo "   - markdown_files (Markdown文件)\n";
    echo "   - users (用户管理)\n";
    echo "\n🔗 API端点:\n";
    echo "   - GET /api.php/databases - 获取所有数据库\n";
    echo "   - POST /api.php/databases - 添加数据库\n";
    echo "   - PUT /api.php/databases/{id} - 更新数据库\n";
    echo "   - DELETE /api.php/databases/{id} - 删除数据库\n";
    echo "   - GET /api.php/markdown - 获取Markdown文件列表\n";
    echo "   - POST /api.php/markdown - 保存Markdown文件\n";
    echo "\n✨ 现在可以启动Web服务器并使用API了！\n";
    
} catch (PDOException $e) {
    echo "❌ 数据库连接失败: " . $e->getMessage() . "\n";
    echo "请检查数据库配置:\n";
    echo "- 主机: {$dbConfig['host']}\n";
    echo "- 用户名: {$dbConfig['username']}\n";
    echo "- 密码: " . (empty($dbConfig['password']) ? '(空)' : '***') . "\n";
    echo "- 数据库名: {$dbConfig['dbname']}\n";
} catch (Exception $e) {
    echo "❌ 错误: " . $e->getMessage() . "\n";
}
?>
