# PHP版本安装说明

## 🚀 快速开始

### 1. 环境要求
- **PHP**: 7.4+ (推荐 8.0+)
- **MySQL**: 5.7+ 或 MariaDB 10.3+
- **Web服务器**: Apache 或 Nginx
- **扩展**: PDO, PDO_MySQL

### 2. 安装步骤

#### 步骤1: 下载文件
将所有文件上传到你的Web服务器目录：
```
/www/wwwroot/18.139.38.2_2222/
├── index.html          # 主应用文件
├── api.php             # PHP API后端
├── init_db.php         # 数据库初始化脚本
├── config.php          # 配置文件
└── README.md           # 说明文档
```

#### 步骤2: 配置数据库
编辑 `config.php` 文件，修改数据库连接信息：
```php
'database' => [
    'host' => 'localhost',        // 你的数据库主机
    'dbname' => 'markdown_db_manager',  // 数据库名
    'username' => 'your_username',      // 你的数据库用户名
    'password' => 'your_password',      // 你的数据库密码
    'charset' => 'utf8mb4'
],
```

#### 步骤3: 初始化数据库
在浏览器中访问：
```
http://your-domain.com/init_db.php
```

或者通过命令行运行：
```bash
php init_db.php
```

#### 步骤4: 访问应用
在浏览器中访问：
```
http://your-domain.com/index.html
```

## 🔧 配置说明

### 数据库配置
- **host**: MySQL服务器地址
- **dbname**: 数据库名称
- **username**: 数据库用户名
- **password**: 数据库密码
- **charset**: 字符集（推荐utf8mb4）

### 应用配置
- **name**: 应用名称
- **version**: 版本号
- **debug**: 调试模式（生产环境建议设为false）
- **timezone**: 时区设置

### 安全配置
- **max_file_size**: 最大文件上传大小
- **allowed_extensions**: 允许的文件扩展名
- **session_timeout**: 会话超时时间

## 📊 数据库结构

### databases 表
| 字段 | 类型 | 说明 |
|------|------|------|
| id | int(11) | 主键，自增 |
| name | varchar(255) | 数据库名 |
| username | varchar(255) | 用户名 |
| password | varchar(255) | 密码 |
| capacity | decimal(10,2) | 容量(GB) |
| backup_status | enum('是','否') | 备份状态 |
| location | varchar(255) | 数据库位置 |
| remark | text | 备注 |
| status | enum('online','offline') | 状态 |
| created_at | timestamp | 创建时间 |
| updated_at | timestamp | 更新时间 |

### markdown_files 表
| 字段 | 类型 | 说明 |
|------|------|------|
| id | int(11) | 主键，自增 |
| filename | varchar(255) | 文件名 |
| content | longtext | 文件内容 |
| created_at | timestamp | 创建时间 |
| updated_at | timestamp | 更新时间 |

### users 表（预留）
| 字段 | 类型 | 说明 |
|------|------|------|
| id | int(11) | 主键，自增 |
| username | varchar(50) | 用户名 |
| email | varchar(100) | 邮箱 |
| password_hash | varchar(255) | 密码哈希 |
| role | enum('admin','user') | 角色 |
| status | enum('active','inactive') | 状态 |
| created_at | timestamp | 创建时间 |
| updated_at | timestamp | 更新时间 |

## 🔌 API接口

### 数据库管理
- `GET /api.php/databases` - 获取所有数据库
- `GET /api.php/databases/{id}` - 获取单个数据库
- `POST /api.php/databases` - 添加数据库
- `PUT /api.php/databases/{id}` - 更新数据库
- `DELETE /api.php/databases/{id}` - 删除数据库
- `GET /api.php/databases/search?q={keyword}` - 搜索数据库
- `GET /api.php/stats` - 获取统计信息

### Markdown文件
- `GET /api.php/markdown` - 获取文件列表
- `GET /api.php/markdown/{filename}` - 获取文件内容
- `POST /api.php/markdown` - 保存文件
- `DELETE /api.php/markdown/{filename}` - 删除文件

## 🛠️ 故障排除

### 常见问题

#### 1. 数据库连接失败
**错误**: `数据库连接失败`
**解决**:
- 检查 `config.php` 中的数据库配置
- 确认MySQL服务正在运行
- 验证用户名和密码是否正确
- 检查数据库是否存在

#### 2. API调用失败
**错误**: `API调用失败`
**解决**:
- 检查 `api.php` 文件是否存在
- 确认Web服务器支持PHP
- 检查文件权限
- 查看Web服务器错误日志

#### 3. 权限问题
**错误**: `Permission denied`
**解决**:
- 设置正确的文件权限: `chmod 644 *.php`
- 确保Web服务器有读取权限
- 检查目录权限

#### 4. 字符编码问题
**错误**: 中文显示乱码
**解决**:
- 确保数据库字符集为 `utf8mb4`
- 检查PHP文件编码为 `UTF-8`
- 确认HTTP头设置正确

### 调试模式
在 `config.php` 中设置 `debug => true` 可以启用详细错误信息。

## 🔒 安全建议

### 生产环境配置
1. **修改默认配置**:
   - 更改数据库密码
   - 使用强密码
   - 限制数据库用户权限

2. **文件权限**:
   ```bash
   chmod 644 *.php
   chmod 644 *.html
   chmod 600 config.php  # 配置文件权限更严格
   ```

3. **Web服务器配置**:
   - 禁用PHP错误显示
   - 设置适当的CORS策略
   - 启用HTTPS

4. **数据库安全**:
   - 使用非root用户
   - 限制远程访问
   - 定期备份数据

## 📈 性能优化

### 数据库优化
- 为常用查询字段添加索引
- 定期清理无用数据
- 使用连接池

### 前端优化
- 启用Gzip压缩
- 使用CDN加速静态资源
- 实现缓存策略

## 🔄 升级说明

### 从v1.0升级到v2.0
1. 备份现有数据
2. 运行 `init_db.php` 创建新表结构
3. 迁移localStorage数据到数据库
4. 更新前端代码

### 数据迁移
如果需要从localStorage迁移数据到数据库，可以：
1. 导出localStorage数据
2. 使用API接口批量导入
3. 验证数据完整性

## 📞 技术支持

如果遇到问题，请：
1. 检查错误日志
2. 确认环境配置
3. 查看API响应
4. 联系技术支持

## 📝 更新日志

### v2.0.0 (2024-01-01)
- ✅ 添加PHP后端支持
- ✅ 实现MySQL数据库持久化
- ✅ 支持跨设备数据同步
- ✅ 添加RESTful API接口
- ✅ 改进错误处理机制
- ✅ 增强安全性

### v1.0.0 (2024-01-01)
- ✅ 基础Markdown编辑器
- ✅ 数据库管理功能
- ✅ 本地存储支持
- ✅ 响应式设计
