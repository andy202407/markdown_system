<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/database.php';

// 配置
$config = [
    'storage_path' => 'markdown_files/',  // 使用相对路径
    'allowed_extensions' => ['md'],
    'max_file_size' => 10 * 1024 * 1024 // 10MB
];

// 设置错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 确保存储目录存在
try {
    if (!file_exists($config['storage_path'])) {
        if (!mkdir($config['storage_path'], 0777, true)) {
            throw new Exception('无法创建存储目录: ' . $config['storage_path']);
        }
        chmod($config['storage_path'], 0777);  // 确保目录可写
    }
} catch (Exception $e) {
    error_log('创建目录失败: ' . $e->getMessage());
    sendError('存储目录创建失败: ' . $e->getMessage());
}

// 处理预检请求
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// 错误处理函数
function sendError($message, $code = 400) {
    error_log('API错误: ' . $message);  // 记录错误到日志
    http_response_code($code);
    echo json_encode([
        'error' => $message,
        'debug' => [
            'storage_path' => realpath('markdown_files/'),
            'php_user' => get_current_user(),
            'script_owner' => fileowner(__FILE__),
            'is_writable' => is_writable('markdown_files/')
        ]
    ]);
    exit;
}

// 成功响应函数
function sendSuccess($data = null, $message = '操作成功') {
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// 简单的分享密码校验（前端通过 ?token= 或 header 传递 password）
function checkShareAccess(DatabaseConfig $db, $filename = null) {
    // 检查是否是分享链接访问
    $isShare = isset($_GET['share']) || isset($_POST['share']);
    
    // 如果不是分享链接访问，直接返回true
    if (!$isShare) {
        return true;
    }
    
    // 文件锁检查（仅针对分享链接）
    if ($filename) {
        $locked = $db->getLockedFiles();
        if (in_array($filename, $locked, true)) {
            sendError('该文件已被上锁，暂不允许外部访问', 403);
        }
    }
    
    // 分享密码检查（为空表示不需要密码）
    $hash = $db->getSharePasswordHash();
    if (!$hash) {
        return true;
    }
    
    $password = null;
    if (isset($_GET['password'])) $password = $_GET['password'];
    if (!$password && isset($_POST['password'])) {
        $password = $_POST['password'];
    }
    if (!$password) {
        sendError('需要分享密码', 401);
    }
    if (!password_verify($password, $hash)) {
        sendError('分享密码不正确', 401);
    }
    return true;
}

// 获取请求方法和路径
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/markdown_api.php', '', $path);

try {
    $db = getDB();
    switch ($method) {
        case 'GET':
            if ($path == '/files' || $path == '/files/') {
                // 获取文件列表（返回锁定状态）
                $locked = $db->getLockedFiles();
                $files = [];
                foreach (glob($config['storage_path'] . '*.md') as $file) {
                    $filename = basename($file);
                    $files[$filename] = [
                        'name' => $filename,
                        'locked' => in_array($filename, $locked, true),
                        'created' => filectime($file),
                        'updated' => filemtime($file)
                    ];
                }
                sendSuccess($files);
            } else if (preg_match('/^\/files\/(.+)$/', $path, $matches)) {
                // 获取单个文件（需要检查锁/密码）
                $filename = urldecode($matches[1]);
                checkShareAccess($db, $filename);
                $filepath = $config['storage_path'] . $filename;
                
                if (!file_exists($filepath)) {
                    sendError('文件不存在', 404);
                }
                
                $file = [
                    'name' => $filename,
                    'content' => file_get_contents($filepath),
                    'created' => filectime($filepath),
                    'updated' => filemtime($filepath)
                ];
                sendSuccess($file);
            }
            break;
            
        case 'POST':
            if ($path == '/files' || $path == '/files/') {
                // 创建或更新文件（后台使用，无需分享密码）
                $input = json_decode(file_get_contents('php://input'), true);
                if (!$input || !isset($input['name']) || !isset($input['content'])) {
                    sendError('无效的请求数据');
                }
                
                $filename = $input['name'];
                if (!preg_match('/\.md$/', $filename)) {
                    $filename .= '.md';
                }
                
                $filepath = $config['storage_path'] . $filename;
                file_put_contents($filepath, $input['content']);
                
                $file = [
                    'name' => $filename,
                    'content' => $input['content'],
                    'created' => filectime($filepath),
                    'updated' => filemtime($filepath)
                ];
                sendSuccess($file, '文件保存成功');
            }
            break;
            
        case 'DELETE':
            if (preg_match('/^\/files\/(.+)$/', $path, $matches)) {
                // 删除文件（后台使用）
                $filename = urldecode($matches[1]);
                $filepath = $config['storage_path'] . $filename;
                
                if (!file_exists($filepath)) {
                    sendError('文件不存在', 404);
                }
                
                unlink($filepath);
                sendSuccess(null, '文件删除成功');
            }
            break;
            
        default:
            sendError('不支持的请求方法', 405);
    }
} catch (Exception $e) {
    sendError($e->getMessage(), 500);
}
?>
