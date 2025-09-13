<?php
/**
 * Markdown文档管理系统 - 安装API
 * 处理安装过程中的各种操作
 */

require_once 'database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// 只允许POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// 获取请求数据
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['action'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$action = $input['action'];

// 处理不同的操作
switch ($action) {
    case 'install':
        installSystem();
        break;
        
    case 'check_database':
        checkDatabase();
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
        break;
}

function installSystem() {
    try {
        // 1. 创建数据库表并初始化配置
        $db = getDB();
        $db->createTables();
        
        // 3. 创建文件存储目录
        $directories = [
            'markdown_files',
            'backups',
            'uploads',
            'logs'
        ];
        
        foreach ($directories as $dir) {
            if (!file_exists($dir)) {
                if (!mkdir($dir, 0755, true)) {
                    throw new Exception("无法创建目录: {$dir}");
                }
            }
        }
        
        // 4. 创建示例文件
        $sampleContent = "# 欢迎使用Markdown文档管理系统\n\n";
        $sampleContent .= "这是一个示例文档，您可以：\n\n";
        $sampleContent .= "- 创建新的Markdown文档\n";
        $sampleContent .= "- 编辑现有文档\n";
        $sampleContent .= "- 管理文档结构\n";
        $sampleContent .= "- 设置访问权限\n\n";
        $sampleContent .= "## 系统功能\n\n";
        $sampleContent .= "1. **文档管理**: 创建、编辑、删除Markdown文件\n";
        $sampleContent .= "2. **权限控制**: 支持公开访问或需要登录\n";
        $sampleContent .= "3. **IP白名单**: 限制管理后台访问IP\n";
        $sampleContent .= "4. **安全认证**: 密码加密存储\n";
        $sampleContent .= "5. **数据库配置**: 所有配置存储在数据库中\n\n";
        $sampleContent .= "## 开始使用\n\n";
        $sampleContent .= "请访问管理后台开始创建您的文档。\n\n";
        $sampleContent .= "---\n";
        $sampleContent .= "*系统安装时间: " . date('Y-m-d H:i:s') . "*\n";
        
        file_put_contents('markdown_files/欢迎使用.md', $sampleContent);
        
        // 5. 数据库配置已通过环境变量管理，无需创建db_config.php
        
        // 6. 创建.htaccess文件（如果不存在）
        $htaccessContent = "# Markdown文档管理系统\n";
        $htaccessContent .= "# 禁止直接访问敏感文件\n";
        $htaccessContent .= "<Files \"db_config.php\">\n";
        $htaccessContent .= "    Order Allow,Deny\n";
        $htaccessContent .= "    Deny from all\n";
        $htaccessContent .= "</Files>\n\n";
        $htaccessContent .= "<Files \"*.log\">\n";
        $htaccessContent .= "    Order Allow,Deny\n";
        $htaccessContent .= "    Deny from all\n";
        $htaccessContent .= "</Files>\n\n";
        $htaccessContent .= "# 启用压缩\n";
        $htaccessContent .= "<IfModule mod_deflate.c>\n";
        $htaccessContent .= "    AddOutputFilterByType DEFLATE text/plain\n";
        $htaccessContent .= "    AddOutputFilterByType DEFLATE text/html\n";
        $htaccessContent .= "    AddOutputFilterByType DEFLATE text/xml\n";
        $htaccessContent .= "    AddOutputFilterByType DEFLATE text/css\n";
        $htaccessContent .= "    AddOutputFilterByType DEFLATE application/xml\n";
        $htaccessContent .= "    AddOutputFilterByType DEFLATE application/xhtml+xml\n";
        $htaccessContent .= "    AddOutputFilterByType DEFLATE application/rss+xml\n";
        $htaccessContent .= "    AddOutputFilterByType DEFLATE application/javascript\n";
        $htaccessContent .= "    AddOutputFilterByType DEFLATE application/x-javascript\n";
        $htaccessContent .= "</IfModule>\n";
        
        if (!file_exists('.htaccess')) {
            file_put_contents('.htaccess', $htaccessContent);
        }
        
        
        echo json_encode([
            'success' => true,
            'message' => '系统安装成功',
            'config' => [
                'admin_username' => 'admin',
                'frontend_access' => 'public',
                'whitelist_ips' => ['180.74.191.129'],
                'database_configured' => true
            ]
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function checkDatabase() {
    try {
        $db = getDB();
        $config = $db->getSystemConfig();
        
        echo json_encode([
            'success' => true,
            'database_connected' => true,
            'config_exists' => !empty($config),
            'config' => $config
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage(),
            'database_connected' => false
        ]);
    }
}
?>
