<?php
session_start();

require_once 'database.php';

// 设置响应头
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
$clientIP = getClientIP();

// 处理不同的操作
switch ($action) {
    case 'login':
        handleLogin($input, $clientIP);
        break;
        
    case 'check':
        handleCheck();
        break;
        
    case 'logout':
        handleLogout();
        break;
        
    case 'change_password':
        handleChangePassword($input);
        break;
        
    case 'get_whitelist':
        handleGetWhitelist();
        break;
        
    case 'update_whitelist':
        handleUpdateWhitelist($input);
        break;
        
    case 'get_frontend_access':
        handleGetFrontendAccess();
        break;
        
    case 'update_frontend_access':
        handleUpdateFrontendAccess($input);
        break;
        
    case 'check_frontend_access':
        handleCheckFrontendAccess();
        break;
        
    case 'get_share_settings':
        handleGetShareSettings();
        break;
    case 'update_share_password':
        handleUpdateSharePassword($input);
        break;
    case 'set_file_lock':
        handleSetFileLock($input);
        break;
    case 'get_locked_files':
        handleGetLockedFiles();
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
        break;
}

// 获取客户端IP
function getClientIP() {
    $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    foreach ($ipKeys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = trim(explode(',', $_SERVER[$key])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

// 验证密码
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// 生成密码哈希
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// 检查IP白名单
function checkIPWhitelist($ip, $whitelist) {
    // 首先检查环境变量中的默认白名单IP
    $defaultWhitelistIPs = env('DEFAULT_WHITELIST_IP', '');
    if (!empty($defaultWhitelistIPs)) {
        $defaultIPs = array_map('trim', explode(',', $defaultWhitelistIPs));
        if (in_array($ip, $defaultIPs)) {
            return true;
        }
    }
    
    // 然后检查数据库中的白名单
    return in_array($ip, $whitelist);
}

// 检查会话是否有效
function isSessionValid() {
    if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
        return false;
    }
    
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > 3600) {
        session_destroy();
        return false;
    }
    
    $_SESSION['last_activity'] = time();
    return true;
}

// 处理登录
function handleLogin($input, $clientIP) {
    try {
        $db = getDB();
        $config = $db->getSystemConfig();
        
        $username = $input['username'] ?? '';
        $password = $input['password'] ?? '';
        
        // 检查IP白名单
        if (!checkIPWhitelist($clientIP, $config['whitelist_ips'])) {
            $db->log('warning', 'IP白名单验证失败', ['ip' => $clientIP, 'username' => $username]);
            echo json_encode([
                'success' => false, 
                'message' => '访问被拒绝：IP地址不在白名单中'
            ]);
            exit;
        }
        
        // 验证用户名和密码
        if ($username === $config['admin_username'] && verifyPassword($password, $config['admin_password_hash'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $username;
            $_SESSION['last_activity'] = time();
            $_SESSION['login_ip'] = $clientIP;
            
            $db->log('info', '管理员登录成功', ['username' => $username, 'ip' => $clientIP]);
            
            echo json_encode([
                'success' => true, 
                'message' => '登录成功',
                'username' => $username
            ]);
        } else {
            $db->log('warning', '登录失败', ['username' => $username, 'ip' => $clientIP]);
            echo json_encode([
                'success' => false, 
                'message' => '用户名或密码错误'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false, 
            'message' => '登录失败: ' . $e->getMessage()
        ]);
    }
}

// 处理认证检查
function handleCheck() {
    try {
        if (isSessionValid()) {
            echo json_encode([
                'success' => true, 
                'authenticated' => true,
                'username' => $_SESSION['admin_username'] ?? '',
                'login_ip' => $_SESSION['login_ip'] ?? ''
            ]);
        } else {
            echo json_encode([
                'success' => true, 
                'authenticated' => false
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false, 
            'message' => '认证检查失败: ' . $e->getMessage()
        ]);
    }
}

// 处理退出登录
function handleLogout() {
    try {
        $db = getDB();
        $db->log('info', '管理员退出登录', ['username' => $_SESSION['admin_username'] ?? '']);
        
        session_destroy();
        echo json_encode([
            'success' => true, 
            'message' => '已退出登录'
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false, 
            'message' => '退出登录失败: ' . $e->getMessage()
        ]);
    }
}

// 处理密码修改
function handleChangePassword($input) {
    try {
        if (!isSessionValid()) {
            echo json_encode(['success' => false, 'message' => '请先登录']);
            exit;
        }
        
        $db = getDB();
        $config = $db->getSystemConfig();
        
        $oldPassword = $input['old_password'] ?? '';
        $newPassword = $input['new_password'] ?? '';
        
        if (empty($oldPassword) || empty($newPassword)) {
            echo json_encode(['success' => false, 'message' => '请填写完整信息']);
            exit;
        }
        
        if (strlen($newPassword) < 6) {
            echo json_encode(['success' => false, 'message' => '新密码长度至少6位']);
            exit;
        }
        
        // 验证旧密码
        if (!verifyPassword($oldPassword, $config['admin_password_hash'])) {
            $db->log('warning', '密码修改失败-原密码错误', ['username' => $_SESSION['admin_username']]);
            echo json_encode(['success' => false, 'message' => '原密码错误']);
            exit;
        }
        
        // 更新密码
        $newHash = hashPassword($newPassword);
        $db->updateAdminPassword($newHash);
        
        $db->log('info', '管理员密码修改成功', ['username' => $_SESSION['admin_username']]);
        
        echo json_encode(['success' => true, 'message' => '密码修改成功']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => '密码修改失败: ' . $e->getMessage()]);
    }
}

// 处理获取白名单
function handleGetWhitelist() {
    try {
        if (!isSessionValid()) {
            echo json_encode(['success' => false, 'message' => '请先登录']);
            exit;
        }
        
        $db = getDB();
        $config = $db->getSystemConfig();
        
        echo json_encode(['success' => true, 'whitelist' => $config['whitelist_ips']]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => '获取白名单失败: ' . $e->getMessage()]);
    }
}

// 处理更新白名单
function handleUpdateWhitelist($input) {
    try {
        if (!isSessionValid()) {
            echo json_encode(['success' => false, 'message' => '请先登录']);
            exit;
        }
        
        $db = getDB();
        $newWhitelist = $input['whitelist'] ?? [];
        
        // 验证IP地址格式
        foreach ($newWhitelist as $ip) {
            if (!filter_var($ip, FILTER_VALIDATE_IP)) {
                echo json_encode(['success' => false, 'message' => "无效的IP地址: {$ip}"]);
                exit;
            }
        }
        
        $db->updateWhitelist($newWhitelist);
        
        $db->log('info', 'IP白名单更新', ['username' => $_SESSION['admin_username'], 'whitelist' => $newWhitelist]);
        
        echo json_encode(['success' => true, 'message' => 'IP白名单更新成功']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'IP白名单更新失败: ' . $e->getMessage()]);
    }
}

// 处理获取前端访问设置
function handleGetFrontendAccess() {
    try {
        if (!isSessionValid()) {
            echo json_encode(['success' => false, 'message' => '请先登录']);
            exit;
        }
        
        $db = getDB();
        $config = $db->getSystemConfig();
        
        echo json_encode(['success' => true, 'frontend_access' => $config['frontend_access']]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => '获取前端访问设置失败: ' . $e->getMessage()]);
    }
}

// 处理更新前端访问设置
function handleUpdateFrontendAccess($input) {
    try {
        if (!isSessionValid()) {
            echo json_encode(['success' => false, 'message' => '请先登录']);
            exit;
        }
        
        $db = getDB();
        $newAccess = $input['frontend_access'] ?? '';
        
        if (!in_array($newAccess, ['public', 'private'])) {
            echo json_encode(['success' => false, 'message' => '无效的访问设置']);
            exit;
        }
        
        $db->updateFrontendAccess($newAccess);
        
        $db->log('info', '前端访问设置更新', ['username' => $_SESSION['admin_username'], 'access' => $newAccess]);
        
        echo json_encode(['success' => true, 'message' => '前端访问设置更新成功']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => '前端访问设置更新失败: ' . $e->getMessage()]);
    }
}

// 处理检查前端访问权限
function handleCheckFrontendAccess() {
    try {
        // 前端直接访问不需要验证，直接返回成功
        echo json_encode([
            'success' => true, 
            'frontend_access' => 'public',
            'require_login' => false
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => '检查前端访问权限失败: ' . $e->getMessage(),
            'require_login' => false
        ]);
    }
}

// 获取分享设置（是否设置密码、锁定列表）
function handleGetShareSettings() {
    try {
        if (!isSessionValid()) {
            echo json_encode(['success' => false, 'message' => '请先登录']);
            return;
        }
        $db = getDB();
        $hasPassword = $db->getSharePasswordHash() ? true : false;
        $lockedFiles = $db->getLockedFiles();
        echo json_encode(['success' => true, 'has_password' => $hasPassword, 'locked_files' => $lockedFiles]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => '获取分享设置失败: ' . $e->getMessage()]);
    }
}

// 更新分享密码（留空表示取消需求）
function handleUpdateSharePassword($input) {
    try {
        if (!isSessionValid()) {
            echo json_encode(['success' => false, 'message' => '请先登录']);
            return;
        }
        $password = $input['share_password'] ?? null;
        $db = getDB();
        $db->updateSharePassword($password);
        echo json_encode(['success' => true, 'message' => '分享密码已更新']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => '更新分享密码失败: ' . $e->getMessage()]);
    }
}

// 设置/取消文件锁
function handleSetFileLock($input) {
    try {
        if (!isSessionValid()) {
            echo json_encode(['success' => false, 'message' => '请先登录']);
            return;
        }
        $filename = $input['filename'] ?? '';
        $locked = isset($input['locked']) ? (bool)$input['locked'] : false;
        if ($filename === '') {
            echo json_encode(['success' => false, 'message' => '缺少文件名']);
            return;
        }
        $db = getDB();
        $db->setFileLock($filename, $locked);
        echo json_encode(['success' => true, 'message' => '文件锁状态已更新']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => '更新文件锁失败: ' . $e->getMessage()]);
    }
}

function handleGetLockedFiles() {
    try {
        if (!isSessionValid()) {
            echo json_encode(['success' => false, 'message' => '请先登录']);
            return;
        }
        $db = getDB();
        echo json_encode(['success' => true, 'locked_files' => $db->getLockedFiles()]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => '获取锁定文件失败: ' . $e->getMessage()]);
    }
}

?>
