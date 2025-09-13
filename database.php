<?php
/**
 * Markdown文档管理系统 - 数据库配置
 * 数据库连接和配置管理
 */

class DatabaseConfig {
    private $pdo;
    private $config;
    
    public function __construct() {
        $this->loadConfig();
        $this->connect();
    }
    
    private function loadConfig() {
        // 数据库配置
        $this->config = [
            'host' => 'localhost',
            'dbname' => 'markdown_system',
            'username' => 'markdown_system',
            'password' => 'NirzkRjDZA8CcWw4',
            'charset' => 'utf8mb4'
        ];
        
        // 如果存在数据库配置文件，加载数据库配置
        if (file_exists('db_config.php')) {
            include 'db_config.php';
            if (isset($db_config)) {
                $this->config = array_merge($this->config, $db_config);
            }
        }
    }
    
    private function connect() {
        try {
            $dsn = "mysql:host={$this->config['host']};dbname={$this->config['dbname']};charset={$this->config['charset']}";
            $this->pdo = new PDO($dsn, $this->config['username'], $this->config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            throw new Exception("数据库连接失败: " . $e->getMessage());
        }
    }
    
    public function getPDO() {
        return $this->pdo;
    }
    
    // 获取系统配置
    public function getSystemConfig() {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM system_config WHERE id = 1");
            $stmt->execute();
            $config = $stmt->fetch();
            
            if (!$config) {
                // 如果配置不存在，返回默认配置
                return $this->getDefaultConfig();
            }
            
            // 解析JSON字段
            $config['whitelist_ips'] = json_decode($config['whitelist_ips'], true) ?: [];
            $config['system_settings'] = json_decode($config['system_settings'], true) ?: [];
            
            return $config;
        } catch (Exception $e) {
            throw new Exception("获取系统配置失败: " . $e->getMessage());
        }
    }
    
    // 更新系统配置
    public function updateSystemConfig($data) {
        try {
            $this->pdo->beginTransaction();
            
            // 准备数据
            $whitelist_ips = json_encode($data['whitelist_ips'] ?? []);
            $system_settings = json_encode($data['system_settings'] ?? []);
            $updated_at = date('Y-m-d H:i:s');
            
            $stmt = $this->pdo->prepare("
                INSERT INTO system_config (
                    id, admin_username, admin_password_hash, whitelist_ips, 
                    frontend_access, session_timeout, system_settings, updated_at, share_password_hash
                ) VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    admin_username = VALUES(admin_username),
                    admin_password_hash = VALUES(admin_password_hash),
                    whitelist_ips = VALUES(whitelist_ips),
                    frontend_access = VALUES(frontend_access),
                    session_timeout = VALUES(session_timeout),
                    system_settings = VALUES(system_settings),
                    share_password_hash = VALUES(share_password_hash),
                    updated_at = VALUES(updated_at)
            ");
            
            $stmt->execute([
                $data['admin_username'] ?? 'admin',
                $data['admin_password_hash'] ?? password_hash('Qwer123.', PASSWORD_DEFAULT),
                $whitelist_ips,
                $data['frontend_access'] ?? 'public',
                $data['session_timeout'] ?? 3600,
                $system_settings,
                $updated_at,
                $data['share_password_hash'] ?? null
            ]);
            
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw new Exception("更新系统配置失败: " . $e->getMessage());
        }
    }
    
    // 更新管理员密码
    public function updateAdminPassword($newPasswordHash) {
        try {
            $stmt = $this->pdo->prepare("UPDATE system_config SET admin_password_hash = ?, updated_at = ? WHERE id = 1");
            $stmt->execute([$newPasswordHash, date('Y-m-d H:i:s')]);
            return true;
        } catch (Exception $e) {
            throw new Exception("更新密码失败: " . $e->getMessage());
        }
    }
    
    // 更新IP白名单
    public function updateWhitelist($whitelist) {
        try {
            $stmt = $this->pdo->prepare("UPDATE system_config SET whitelist_ips = ?, updated_at = ? WHERE id = 1");
            $stmt->execute([json_encode($whitelist), date('Y-m-d H:i:s')]);
            return true;
        } catch (Exception $e) {
            throw new Exception("更新IP白名单失败: " . $e->getMessage());
        }
    }
    
    // 更新前端访问控制
    public function updateFrontendAccess($access) {
        try {
            $stmt = $this->pdo->prepare("UPDATE system_config SET frontend_access = ?, updated_at = ? WHERE id = 1");
            $stmt->execute([$access, date('Y-m-d H:i:s')]);
            return true;
        } catch (Exception $e) {
            throw new Exception("更新前端访问控制失败: " . $e->getMessage());
        }
    }
    
    // 更新分享密码（留空表示取消）
    public function updateSharePassword(?string $plainPassword) {
        try {
            $hash = null;
            if ($plainPassword !== null && trim($plainPassword) !== '') {
                $hash = password_hash($plainPassword, PASSWORD_DEFAULT);
            }
            $stmt = $this->pdo->prepare("UPDATE system_config SET share_password_hash = ?, updated_at = ? WHERE id = 1");
            $stmt->execute([$hash, date('Y-m-d H:i:s')]);
            return true;
        } catch (Exception $e) {
            throw new Exception("更新分享密码失败: " . $e->getMessage());
        }
    }

    // 获取分享密码哈希
    public function getSharePasswordHash(): ?string {
        $stmt = $this->pdo->query("SELECT share_password_hash FROM system_config WHERE id = 1");
        $row = $stmt->fetch();
        return $row ? ($row['share_password_hash'] ?? null) : null;
    }

    // 获取上锁文件列表
    public function getLockedFiles(): array {
        $cfg = $this->getSystemConfig();
        $settings = $cfg['system_settings'] ?? [];
        return isset($settings['locked_files']) && is_array($settings['locked_files']) ? $settings['locked_files'] : [];
    }

    // 设置文件锁状态
    public function setFileLock(string $filename, bool $locked): bool {
        $cfg = $this->getSystemConfig();
        $settings = $cfg['system_settings'] ?? [];
        if (!isset($settings['locked_files']) || !is_array($settings['locked_files'])) {
            $settings['locked_files'] = [];
        }
        $lockedFiles = $settings['locked_files'];
        if ($locked) {
            if (!in_array($filename, $lockedFiles, true)) {
                $lockedFiles[] = $filename;
            }
        } else {
            $lockedFiles = array_values(array_filter($lockedFiles, function($f) use ($filename) { return $f !== $filename; }));
        }
        $settings['locked_files'] = $lockedFiles;
        return $this->updateSystemConfig([
            'system_settings' => $settings
        ]);
    }
    
    // 获取默认配置
    private function getDefaultConfig() {
        return [
            'id' => 1,
            'admin_username' => 'admin',
            'admin_password_hash' => password_hash('Qwer123.', PASSWORD_DEFAULT),
            'whitelist_ips' => ['180.74.191.129'],
            'frontend_access' => 'public',
            'session_timeout' => 3600,
            'system_settings' => [
                'max_file_size' => 10485760,
                'allowed_extensions' => ['md', 'txt'],
                'backup_enabled' => true,
                'auto_save_interval' => 30000,
                'locked_files' => []
            ],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
    }
    
    // 创建数据库表
    public function createTables() {
        try {
            // 创建系统配置表
            $sql = "
                CREATE TABLE IF NOT EXISTS system_config (
                    id INT PRIMARY KEY DEFAULT 1,
                    admin_username VARCHAR(50) NOT NULL DEFAULT 'admin',
                    admin_password_hash VARCHAR(255) NOT NULL,
                    share_password_hash VARCHAR(255) NULL,
                    whitelist_ips TEXT NOT NULL,
                    frontend_access ENUM('public', 'private') NOT NULL DEFAULT 'public',
                    session_timeout INT NOT NULL DEFAULT 3600,
                    system_settings TEXT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
            
            $this->pdo->exec($sql);
            
            // 迁移: 若列不存在则添加 share_password_hash
            try {
                $this->pdo->exec("ALTER TABLE system_config ADD COLUMN share_password_hash VARCHAR(255) NULL AFTER admin_password_hash");
            } catch (Exception $e) {
                // 已存在时忽略
            }
            
            // 创建用户会话表
            $sql = "
                CREATE TABLE IF NOT EXISTS user_sessions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    session_id VARCHAR(128) NOT NULL UNIQUE,
                    user_id VARCHAR(50) NOT NULL,
                    ip_address VARCHAR(45) NOT NULL,
                    user_agent TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    expires_at TIMESTAMP NOT NULL,
                    INDEX idx_session_id (session_id),
                    INDEX idx_user_id (user_id),
                    INDEX idx_expires_at (expires_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
            
            $this->pdo->exec($sql);
            
            // 创建系统日志表
            $sql = "
                CREATE TABLE IF NOT EXISTS system_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    level ENUM('info', 'warning', 'error') NOT NULL DEFAULT 'info',
                    message TEXT NOT NULL,
                    context TEXT,
                    ip_address VARCHAR(45),
                    user_agent TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_level (level),
                    INDEX idx_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
            
            $this->pdo->exec($sql);
            
            // 初始化默认配置（非事务）
            $this->initDefaultConfig();
            
            // 记录安装日志（非事务）
            $this->log('info', '系统安装完成', [
                'version' => '1.0.0',
                'admin_username' => 'admin',
                'ip_whitelist' => ['180.74.191.129'],
                'frontend_access' => 'public'
            ]);
            
            return true;
        } catch (Exception $e) {
            throw new Exception("创建数据库表失败: " . $e->getMessage());
        }
    }
    
    // 在事务中初始化默认配置
    private function initDefaultConfigInTransaction() {
        $defaultConfig = $this->getDefaultConfig();
        $whitelist_ips = json_encode($defaultConfig['whitelist_ips']);
        $system_settings = json_encode($defaultConfig['system_settings']);
        
        $stmt = $this->pdo->prepare("
            INSERT INTO system_config (
                id, admin_username, admin_password_hash, whitelist_ips, 
                frontend_access, session_timeout, system_settings
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                admin_username = VALUES(admin_username),
                admin_password_hash = VALUES(admin_password_hash),
                whitelist_ips = VALUES(whitelist_ips),
                frontend_access = VALUES(frontend_access),
                session_timeout = VALUES(session_timeout),
                system_settings = VALUES(system_settings),
                updated_at = CURRENT_TIMESTAMP
        ");
        
        $stmt->execute([
            1,
            $defaultConfig['admin_username'],
            $defaultConfig['admin_password_hash'],
            $whitelist_ips,
            $defaultConfig['frontend_access'],
            $defaultConfig['session_timeout'],
            $system_settings
        ]);
    }
    
    // 在事务中记录日志
    private function logInTransaction($level, $message, $context = null) {
        $stmt = $this->pdo->prepare("
            INSERT INTO system_logs (level, message, context, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $level,
            $message,
            $context ? json_encode($context) : null,
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }
    
    // 初始化默认配置
    public function initDefaultConfig() {
        try {
            $defaultConfig = $this->getDefaultConfig();
            // 直接插入，不使用事务（因为createTables已经处理了事务）
            $whitelist_ips = json_encode($defaultConfig['whitelist_ips']);
            $system_settings = json_encode($defaultConfig['system_settings']);
            
            $stmt = $this->pdo->prepare("
                INSERT INTO system_config (
                    id, admin_username, admin_password_hash, whitelist_ips, 
                    frontend_access, session_timeout, system_settings
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    admin_username = VALUES(admin_username),
                    admin_password_hash = VALUES(admin_password_hash),
                    whitelist_ips = VALUES(whitelist_ips),
                    frontend_access = VALUES(frontend_access),
                    session_timeout = VALUES(session_timeout),
                    system_settings = VALUES(system_settings),
                    updated_at = CURRENT_TIMESTAMP
            ");
            
            $stmt->execute([
                1,
                $defaultConfig['admin_username'],
                $defaultConfig['admin_password_hash'],
                $whitelist_ips,
                $defaultConfig['frontend_access'],
                $defaultConfig['session_timeout'],
                $system_settings
            ]);
            
            return true;
        } catch (Exception $e) {
            throw new Exception("初始化默认配置失败: " . $e->getMessage());
        }
    }
    
    // 记录系统日志
    public function log($level, $message, $context = null) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO system_logs (level, message, context, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $level,
                $message,
                $context ? json_encode($context) : null,
                $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
            
            return true;
        } catch (Exception $e) {
            // 日志记录失败不应该影响主流程
            error_log("日志记录失败: " . $e->getMessage());
            return false;
        }
    }
}

// 全局数据库实例
function getDB() {
    static $db = null;
    if ($db === null) {
        $db = new DatabaseConfig();
    }
    return $db;
}
?>
