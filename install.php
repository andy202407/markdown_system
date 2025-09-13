<?php
/**
 * Markdown文档管理系统 - 安装脚本
 * 用于数据库表生成和系统初始化
 */

require_once 'database.php';

// 检查是否已安装（检查数据库表是否存在）
try {
    $db = getDB();
    $config = $db->getSystemConfig();
    
    // 如果配置存在且不是默认配置，说明已安装
    if (!empty($config) && isset($config['admin_username'])) {
        if (!isset($_GET['force'])) {
            die('系统已安装，如需重新安装请访问 install.php?force=1');
        }
    }
} catch (Exception $e) {
    // 数据库连接失败或表不存在，允许安装
    if (strpos($e->getMessage(), 'Table') === false && strpos($e->getMessage(), 'doesn\'t exist') === false) {
        // 如果是其他数据库错误，显示错误信息
        die('数据库连接失败: ' . $e->getMessage());
    }
}

// 错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 配置
$config = [
    'admin_username' => 'admin',
    'admin_password' => 'Qwer123.',
    'whitelist_ips' => ['180.74.191.129'],
    'frontend_access' => 'public', // public: 公开访问, private: 需要登录
    'session_timeout' => 3600,
    'install_time' => time()
];

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统安装 - Markdown文档管理系统</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .install-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            padding: 40px;
            backdrop-filter: blur(10px);
            max-width: 600px;
            width: 100%;
        }
        
        .install-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .install-icon {
            font-size: 64px;
            color: #667eea;
            margin-bottom: 20px;
        }
        
        .install-title {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
        }
        
        .install-subtitle {
            font-size: 16px;
            color: #6c757d;
        }
        
        .step-item {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .step-item:last-child {
            border-bottom: none;
        }
        
        .step-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 18px;
        }
        
        .step-icon.success {
            background: #d4edda;
            color: #155724;
        }
        
        .step-icon.error {
            background: #f8d7da;
            color: #721c24;
        }
        
        .step-icon.pending {
            background: #e2e3e5;
            color: #6c757d;
        }
        
        .step-content {
            flex: 1;
        }
        
        .step-title {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .step-desc {
            font-size: 14px;
            color: #6c757d;
        }
        
        .btn-install {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            color: white;
            width: 100%;
            margin-top: 20px;
            transition: all 0.3s ease;
        }
        
        .btn-install:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }
        
        .btn-install:disabled {
            opacity: 0.6;
            transform: none;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            margin-bottom: 20px;
        }
        
        .config-preview {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .config-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .config-item:last-child {
            margin-bottom: 0;
        }
        
        .config-label {
            font-weight: 600;
            color: #495057;
        }
        
        .config-value {
            color: #6c757d;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="install-container">
                    <div class="install-header">
                        <div class="install-icon">
                            <i class="fas fa-download"></i>
                        </div>
                        <h1 class="install-title">系统安装</h1>
                        <p class="install-subtitle">Markdown文档管理系统初始化</p>
                    </div>
                    
                    <div id="alertContainer"></div>
                    
                    <div class="config-preview">
                        <h5 class="mb-3">
                            <i class="fas fa-cog me-2"></i>安装配置
                        </h5>
                        <div class="config-item">
                            <span class="config-label">数据库:</span>
                            <span class="config-value">MySQL (markdown_system)</span>
                        </div>
                        <div class="config-item">
                            <span class="config-label">管理员账号:</span>
                            <span class="config-value"><?php echo $config['admin_username']; ?></span>
                        </div>
                        <div class="config-item">
                            <span class="config-label">管理员密码:</span>
                            <span class="config-value"><?php echo $config['admin_password']; ?></span>
                        </div>
                        <div class="config-item">
                            <span class="config-label">IP白名单:</span>
                            <span class="config-value"><?php echo implode(', ', $config['whitelist_ips']); ?></span>
                        </div>
                        <div class="config-item">
                            <span class="config-label">前端访问:</span>
                            <span class="config-value"><?php echo $config['frontend_access'] === 'public' ? '公开访问' : '需要登录'; ?></span>
                        </div>
                    </div>
                    
                    <div id="installSteps">
                        <div class="step-item">
                            <div class="step-icon pending" id="step1-icon">
                                <i class="fas fa-database"></i>
                            </div>
                            <div class="step-content">
                                <div class="step-title" id="step1-title">创建数据库表</div>
                                <div class="step-desc" id="step1-desc">创建系统配置表和日志表</div>
                            </div>
                        </div>
                        
                        <div class="step-item">
                            <div class="step-icon pending" id="step2-icon">
                                <i class="fas fa-cog"></i>
                            </div>
                            <div class="step-content">
                                <div class="step-title" id="step2-title">初始化配置</div>
                                <div class="step-desc" id="step2-desc">设置默认管理员账号和系统配置</div>
                            </div>
                        </div>
                        
                        <div class="step-item">
                            <div class="step-icon pending" id="step3-icon">
                                <i class="fas fa-folder"></i>
                            </div>
                            <div class="step-content">
                                <div class="step-title" id="step3-title">创建文件目录</div>
                                <div class="step-desc" id="step3-desc">创建Markdown文件存储目录</div>
                            </div>
                        </div>
                        
                        <div class="step-item">
                            <div class="step-icon pending" id="step4-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <div class="step-content">
                                <div class="step-title" id="step4-title">设置权限</div>
                                <div class="step-desc" id="step4-desc">配置文件和目录权限</div>
                            </div>
                        </div>
                        
                        <div class="step-item">
                            <div class="step-icon pending" id="step5-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="step-content">
                                <div class="step-title" id="step5-title">完成安装</div>
                                <div class="step-desc" id="step5-desc">系统安装完成，可以开始使用</div>
                            </div>
                        </div>
                    </div>
                    
                    <button class="btn btn-install" id="installBtn" onclick="startInstall()">
                        <i class="fas fa-play me-2"></i>开始安装
                    </button>
                    
                    <div class="text-center mt-3">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            安装完成后请删除 install.php 文件以确保安全
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function showAlert(type, message) {
            const alertContainer = document.getElementById('alertContainer');
            alertContainer.innerHTML = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
        }
        
        function updateStep(stepNum, status, title, desc) {
            const icon = document.getElementById(`step${stepNum}-icon`);
            const titleEl = document.getElementById(`step${stepNum}-title`);
            const descEl = document.getElementById(`step${stepNum}-desc`);
            
            icon.className = `step-icon ${status}`;
            titleEl.textContent = title;
            descEl.textContent = desc;
            
            if (status === 'success') {
                icon.innerHTML = '<i class="fas fa-check"></i>';
            } else if (status === 'error') {
                icon.innerHTML = '<i class="fas fa-times"></i>';
            }
        }
        
        async function startInstall() {
            const installBtn = document.getElementById('installBtn');
            installBtn.disabled = true;
            installBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>安装中...';
            
            try {
                // 步骤1: 创建数据库表
                updateStep(1, 'pending', '创建数据库表', '正在创建系统配置表和日志表...');
                
                const response = await fetch('install_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'install'
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    updateStep(1, 'success', '创建数据库表', '数据库表创建成功');
                    
                    // 步骤2: 初始化配置
                    updateStep(2, 'pending', '初始化配置', '正在设置默认管理员账号和系统配置...');
                    await new Promise(resolve => setTimeout(resolve, 500));
                    updateStep(2, 'success', '初始化配置', '系统配置初始化成功');
                    
                    // 步骤3: 创建文件目录
                    updateStep(3, 'pending', '创建文件目录', '正在创建Markdown文件存储目录...');
                    await new Promise(resolve => setTimeout(resolve, 500));
                    updateStep(3, 'success', '创建文件目录', '文件目录创建成功');
                    
                    // 步骤4: 设置权限
                    updateStep(4, 'pending', '设置权限', '正在配置文件和目录权限...');
                    await new Promise(resolve => setTimeout(resolve, 500));
                    updateStep(4, 'success', '设置权限', '权限设置完成');
                    
                    // 步骤5: 完成安装
                    updateStep(5, 'success', '完成安装', '系统安装完成！');
                    
                    showAlert('success', '系统安装成功！所有配置已保存到数据库。正在跳转到首页...');
                    
                    setTimeout(() => {
                        window.location.href = 'index.html';
                    }, 2000);
                    
                } else {
                    updateStep(1, 'error', '创建数据库表', result.message || '数据库表创建失败');
                    showAlert('danger', result.message || '安装失败');
                    installBtn.disabled = false;
                    installBtn.innerHTML = '<i class="fas fa-play me-2"></i>重新安装';
                }
                
            } catch (error) {
                console.error('安装错误:', error);
                updateStep(1, 'error', '创建数据库表', '网络错误');
                showAlert('danger', '网络错误，请稍后重试');
                installBtn.disabled = false;
                installBtn.innerHTML = '<i class="fas fa-play me-2"></i>重新安装';
            }
        }
    </script>
</body>
</html>
