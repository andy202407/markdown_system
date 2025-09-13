<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// 处理预检请求
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// 加载配置文件
$config = require_once 'config.php';
$dbConfig = $config['database'];

class DatabaseManager {
    private $pdo;
    
    public function __construct($config) {
        try {
            $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
            $this->pdo = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch (PDOException $e) {
            throw new Exception("数据库连接失败: " . $e->getMessage());
        }
    }
    
    // 获取所有数据库记录
    public function getAllDatabases() {
        $stmt = $this->pdo->query("SELECT * FROM databases ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }
    
    // 根据ID获取数据库记录
    public function getDatabaseById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM databases WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    // 添加数据库记录
    public function addDatabase($data) {
        $sql = "INSERT INTO databases (name, username, password, capacity, backup_status, location, remark, status, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $data['name'],
            $data['username'],
            $data['password'],
            $data['capacity'],
            $data['backup_status'],
            $data['location'],
            $data['remark'],
            $data['status']
        ]);
        return $this->pdo->lastInsertId();
    }
    
    // 更新数据库记录
    public function updateDatabase($id, $data) {
        $sql = "UPDATE databases SET 
                name = ?, username = ?, password = ?, capacity = ?, 
                backup_status = ?, location = ?, remark = ?, status = ?, updated_at = NOW()
                WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $data['name'],
            $data['username'],
            $data['password'],
            $data['capacity'],
            $data['backup_status'],
            $data['location'],
            $data['remark'],
            $data['status'],
            $id
        ]);
    }
    
    // 删除数据库记录
    public function deleteDatabase($id) {
        $stmt = $this->pdo->prepare("DELETE FROM databases WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    // 搜索数据库记录
    public function searchDatabases($keyword) {
        $sql = "SELECT * FROM databases WHERE 
                name LIKE ? OR username LIKE ? OR location LIKE ? OR remark LIKE ?
                ORDER BY created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $searchTerm = "%{$keyword}%";
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        return $stmt->fetchAll();
    }
    
    // 获取统计信息
    public function getStats() {
        $stats = [];
        
        // 总数据库数
        $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM databases");
        $stats['total'] = $stmt->fetch()['total'];
        
        // 总容量
        $stmt = $this->pdo->query("SELECT SUM(capacity) as total_capacity FROM databases");
        $stats['total_capacity'] = $stmt->fetch()['total_capacity'] ?? 0;
        
        // 活跃数据库数
        $stmt = $this->pdo->query("SELECT COUNT(*) as active FROM databases WHERE status = 'online'");
        $stats['active'] = $stmt->fetch()['active'];
        
        // 备份数量
        $stmt = $this->pdo->query("SELECT COUNT(*) as backup_count FROM databases WHERE backup_status = '是'");
        $stats['backup_count'] = $stmt->fetch()['backup_count'];
        
        return $stats;
    }
}

class MarkdownManager {
    private $pdo;
    
    public function __construct($config) {
        try {
            $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
            $this->pdo = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch (PDOException $e) {
            throw new Exception("数据库连接失败: " . $e->getMessage());
        }
    }
    
    // 保存Markdown文件
    public function saveMarkdown($filename, $content) {
        $sql = "INSERT INTO markdown_files (filename, content, created_at, updated_at) 
                VALUES (?, ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE content = ?, updated_at = NOW()";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$filename, $content, $content]);
    }
    
    // 获取Markdown文件
    public function getMarkdown($filename) {
        $stmt = $this->pdo->prepare("SELECT * FROM markdown_files WHERE filename = ?");
        $stmt->execute([$filename]);
        return $stmt->fetch();
    }
    
    // 获取所有Markdown文件列表
    public function getAllMarkdownFiles() {
        $stmt = $this->pdo->query("SELECT filename, created_at, updated_at FROM markdown_files ORDER BY updated_at DESC");
        return $stmt->fetchAll();
    }
    
    // 删除Markdown文件
    public function deleteMarkdown($filename) {
        $stmt = $this->pdo->prepare("DELETE FROM markdown_files WHERE filename = ?");
        return $stmt->execute([$filename]);
    }
}

// 错误处理函数
function sendError($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['error' => $message]);
    exit;
}

function sendSuccess($data = null, $message = '操作成功') {
    echo json_encode(['success' => true, 'message' => $message, 'data' => $data]);
    exit;
}

// 获取请求方法和路径
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/api.php', '', $path);

try {
    $dbManager = new DatabaseManager($dbConfig);
    $markdownManager = new MarkdownManager($dbConfig);
    
    switch ($method) {
        case 'GET':
            if ($path === '/databases' || $path === '/databases/') {
                // 获取所有数据库记录
                $databases = $dbManager->getAllDatabases();
                sendSuccess($databases);
                
            } elseif (preg_match('/^\/databases\/(\d+)$/', $path, $matches)) {
                // 获取单个数据库记录
                $id = $matches[1];
                $database = $dbManager->getDatabaseById($id);
                if ($database) {
                    sendSuccess($database);
                } else {
                    sendError('数据库记录不存在', 404);
                }
                
            } elseif ($path === '/databases/search' || $path === '/databases/search/') {
                // 搜索数据库记录
                $keyword = $_GET['q'] ?? '';
                if (empty($keyword)) {
                    $databases = $dbManager->getAllDatabases();
                } else {
                    $databases = $dbManager->searchDatabases($keyword);
                }
                sendSuccess($databases);
                
            } elseif ($path === '/stats' || $path === '/stats/') {
                // 获取统计信息
                $stats = $dbManager->getStats();
                sendSuccess($stats);
                
            } elseif ($path === '/markdown' || $path === '/markdown/') {
                // 获取所有Markdown文件列表
                $files = $markdownManager->getAllMarkdownFiles();
                sendSuccess($files);
                
            } elseif (preg_match('/^\/markdown\/(.+)$/', $path, $matches)) {
                // 获取特定Markdown文件
                $filename = urldecode($matches[1]);
                $file = $markdownManager->getMarkdown($filename);
                if ($file) {
                    sendSuccess($file);
                } else {
                    sendError('文件不存在', 404);
                }
                
            } else {
                sendError('无效的API路径', 404);
            }
            break;
            
        case 'POST':
            if ($path === '/databases' || $path === '/databases/') {
                // 添加数据库记录
                $input = json_decode(file_get_contents('php://input'), true);
                if (!$input) {
                    sendError('无效的JSON数据');
                }
                
                $required = ['name', 'username', 'password'];
                foreach ($required as $field) {
                    if (!isset($input[$field]) || empty($input[$field])) {
                        sendError("缺少必需字段: {$field}");
                    }
                }
                
                $data = [
                    'name' => $input['name'],
                    'username' => $input['username'],
                    'password' => $input['password'],
                    'capacity' => floatval($input['capacity'] ?? 0),
                    'backup_status' => $input['backup_status'] ?? '否',
                    'location' => $input['location'] ?? '',
                    'remark' => $input['remark'] ?? '',
                    'status' => $input['status'] ?? 'online'
                ];
                
                $id = $dbManager->addDatabase($data);
                $database = $dbManager->getDatabaseById($id);
                sendSuccess($database, '数据库添加成功');
                
            } elseif ($path === '/markdown' || $path === '/markdown/') {
                // 保存Markdown文件
                $input = json_decode(file_get_contents('php://input'), true);
                if (!$input) {
                    sendError('无效的JSON数据');
                }
                
                if (!isset($input['filename']) || !isset($input['content'])) {
                    sendError('缺少必需字段: filename 或 content');
                }
                
                $markdownManager->saveMarkdown($input['filename'], $input['content']);
                sendSuccess(null, 'Markdown文件保存成功');
                
            } else {
                sendError('无效的API路径', 404);
            }
            break;
            
        case 'PUT':
            if (preg_match('/^\/databases\/(\d+)$/', $path, $matches)) {
                // 更新数据库记录
                $id = $matches[1];
                $input = json_decode(file_get_contents('php://input'), true);
                if (!$input) {
                    sendError('无效的JSON数据');
                }
                
                $data = [
                    'name' => $input['name'],
                    'username' => $input['username'],
                    'password' => $input['password'],
                    'capacity' => floatval($input['capacity'] ?? 0),
                    'backup_status' => $input['backup_status'] ?? '否',
                    'location' => $input['location'] ?? '',
                    'remark' => $input['remark'] ?? '',
                    'status' => $input['status'] ?? 'online'
                ];
                
                if ($dbManager->updateDatabase($id, $data)) {
                    $database = $dbManager->getDatabaseById($id);
                    sendSuccess($database, '数据库更新成功');
                } else {
                    sendError('更新失败');
                }
                
            } else {
                sendError('无效的API路径', 404);
            }
            break;
            
        case 'DELETE':
            if (preg_match('/^\/databases\/(\d+)$/', $path, $matches)) {
                // 删除数据库记录
                $id = $matches[1];
                if ($dbManager->deleteDatabase($id)) {
                    sendSuccess(null, '数据库删除成功');
                } else {
                    sendError('删除失败');
                }
                
            } elseif (preg_match('/^\/markdown\/(.+)$/', $path, $matches)) {
                // 删除Markdown文件
                $filename = urldecode($matches[1]);
                if ($markdownManager->deleteMarkdown($filename)) {
                    sendSuccess(null, '文件删除成功');
                } else {
                    sendError('删除失败');
                }
                
            } else {
                sendError('无效的API路径', 404);
            }
            break;
            
        default:
            sendError('不支持的HTTP方法', 405);
    }
    
} catch (Exception $e) {
    sendError($e->getMessage(), 500);
}
?>
