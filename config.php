<?php
// 数据库配置文件
// 请根据你的实际环境修改这些配置

return [
    'database' => [
        'host' => 'localhost',        // 数据库主机
        'dbname' => 'markdown',  // 数据库名
        'username' => 'markdown',        // 数据库用户名
        'password' => 'TdNmKy7cysMbd3RG',            // 数据库密码
        'charset' => 'utf8mb4'       // 字符集
    ],
    
    'app' => [
        'name' => 'Markdown编辑器 & 数据库管理系统',
        'version' => '2.0.0',
        'debug' => true,              // 调试模式
        'timezone' => 'Asia/Shanghai'  // 时区
    ],
    
    'security' => [
        'max_file_size' => 10 * 1024 * 1024,  // 最大文件大小 (10MB)
        'allowed_extensions' => ['md', 'txt'], // 允许的文件扩展名
        'session_timeout' => 3600              // 会话超时时间 (秒)
    ]
];
?>
