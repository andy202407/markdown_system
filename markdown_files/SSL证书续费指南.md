# 🔐 SSL证书续费指南

## 📋 快速导航
- [🛠️ Nginx管理](#nginx管理)
- [🔍 证书查询](#证书查询)
- [🔄 证书续费](#证书续费)
- [⚠️ 异常处理](#异常处理)

---

## 🛠️ Nginx管理

### 📁 配置文件目录
```bash
cd /usr/local/nginx/conf/vhost
```

### 🔧 常用命令
```bash
# 测试配置语法是否正确
sudo /usr/local/nginx/sbin/nginx -t

# 重启Nginx
sudo /usr/local/nginx/sbin/nginx -s reload

# 查看进程
ps -ef | grep nginx
```

---

## 🔍 证书查询

### 📊 查询证书有效期
```bash
certbot certificates
```

---

## 🔄 证书续费

### 🌐 API服务器证书 (api.qialiaokefu.com)

**服务器**: `119.28.68.249`

#### 步骤1: 执行SSL续费指令

**首先执行续费命令**:
```bash
sudo certbot certonly --manual -d api.qialiaokefu.com
```

**根据系统提示选择处理方式**:

- **如果系统提示选择1或2**: 直接输入 `2` 按回车确认即可，无需其他操作
- **如果系统提示"Press Enter to Continue"**: 需要先完成后续所有步骤（修改配置、重启Nginx、创建验证文件），完成后再回到续费命令窗口按Enter继续

#### 步骤2: Web界面操作（仅在需要手动验证时执行）

1. **登录Web管理界面**
   - 地址: https://119.28.68.249:10000/
   - 账号: `root`
   - 密码: `[待填写]`

2. **通过Web界面编辑配置文件**
   - 在Webmin界面左侧菜单栏点击 **"工具类"**
   - 在展开的工具类菜单中点击 **"文件管理器"**
   - 在文件管理器中导航到路径: `/usr/local/nginx/conf/vhost/`
   - 找到并编辑文件: `api.qialiaokefu.com.conf`

3. **取消注释并修改配置**
   - 找到以下注释部分并取消注释（删除 `#` 号）：
   ```nginx
   location / {
       root /usr/local/nginx/html;
       index index.html index.htm;
       # 如果 /.well-known/acme-challenge/ 是静态文件目录的一部分，可以在此处理
       location /.well-known/acme-challenge/ {
           default_type "text/plain";
           allow all;  
       }
   }
   ```

4. **保存文件并重启Nginx**
   - 在Web界面中点击保存按钮
   - **按 `Alt + K` 验证配置并重启Nginx**
   ```bash
   # 验证配置文件
   sudo /usr/local/nginx/sbin/nginx -t
   
   # 重启Nginx
   sudo /usr/local/nginx/sbin/nginx -s reload
   ```

#### 步骤3: Web界面创建验证文件

5. **根据SSL续费指令提示创建验证文件**
   - 系统会显示类似以下提示：
   ```
   - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
   Create a file containing just this data:
   
   2iDo5YYUgez2GsaFfSQsCojtLBimlgFmkfSpXLVZhnI.jSvoy0EajKukXLtjoHu8457EZdrKwmaDkN41awBXM84
   
   And make it available on your web server at this URL:
   
   http://api.qialiaokefu.com/.well-known/acme-challenge/2iDo5YYUgez2GsaFfSQsCojtLBimlgFmkfSpXLVZhnI
   ```

6. **使用Alt+K创建验证文件**
   - 在Web界面中按 `Alt + K`
   - 使用指令创建新文件并保存内容：
   ```bash
   echo "2iDo5YYUgez2GsaFfSQsCojtLBimlgFmkfSpXLVZhnI.jSvoy0EajKukXLtjoHu8457EZdrKwmaDkN41awBXM84" > /usr/local/nginx/html/.well-known/acme-challenge/2iDo5YYUgez2GsaFfSQsCojtLBimlgFmkfSpXLVZhnI
   ```
   - 执行指令创建新文件并保存内容

7. **确认续费**
   - 回到Termius终端的续费命令窗口
   - 按 `Enter` 确认继续
   - 等待证书续费完成

#### 步骤4: 完成续费（可选：保留验证路径配置）

8. **续费完成**
   - 证书续费成功后，验证路径配置可以保留
   - **建议保留原因**：
     - 不影响其他访问，只匹配 `/.well-known/acme-challenge/` 路径
     - 方便下次续费，无需重新取消注释
     - 符合Let's Encrypt标准做法
     - 配置轻量，不影响性能

9. **如需注释配置**（可选操作）
   - 重新编辑文件: `api.qialiaokefu.com.conf`
   - 将验证路径配置重新注释：
   ```nginx
   location / {
       root /usr/local/nginx/html;
       index index.html index.htm;
       # 如果 /.well-known/acme-challenge/ 是静态文件目录的一部分，可以在此处理
       # location /.well-known/acme-challenge/ {
       #     default_type "text/plain";
       #     allow all;  
       # }
   }
   ```
   - 保存文件并按 `Alt + K` 验证配置并重启Nginx

---

### 🖥️ CMS服务器证书 (cms.qialiaokefu.com)

**服务器**: `43.132.102.59`

#### 步骤1: 执行SSL续费指令

**首先执行续费命令**:
```bash
sudo certbot certonly --manual -d cms.qialiaokefu.com
```

**根据系统提示选择处理方式**:

- **如果系统提示选择1或2**: 直接输入 `2` 按回车确认即可，无需其他操作
- **如果系统提示"Press Enter to Continue"**: 需要先完成后续所有步骤（修改配置、重启Nginx、创建验证文件），完成后再回到续费命令窗口按Enter继续

#### 步骤2: Web界面操作（仅在需要手动验证时执行）

1. **登录Web管理界面**
   - 地址: https://43.132.102.59:10000/
   - 账号: `root`
   - 密码: `[待填写]`

2. **通过Web界面编辑配置文件**
   - 在Web管理界面中导航到文件管理器
   - 进入路径: `/usr/local/nginx/conf/vhost/`
   - 找到并编辑文件: `cms.qialiaokefu.com.conf`

3. **取消注释并修改配置**
   - 找到以下注释部分并取消注释（删除 `#` 号）：
   ```nginx
        # 如果 /.well-known/acme-challenge/ 是静态文件目录的一部分，可以在此处理
        # location /.well-known/acme-challenge/ {
        #     default_type "text/plain";
        #     allow all;
        # }
   ```

4. **保存文件并重启Nginx**
   - 在Web界面中点击保存按钮
   - **按 `Alt + K` 验证配置并重启Nginx**
   ```bash
   # 验证配置文件
   sudo /usr/local/nginx/sbin/nginx -t
   
   # 重启Nginx
   sudo /usr/local/nginx/sbin/nginx -s reload
   ```

#### 步骤3: Web界面创建验证文件

5. **根据SSL续费指令提示创建验证文件**
   - 系统会显示类似以下提示：
   ```
   - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
   Create a file containing just this data:
   
   2iDo5YYUgez2GsaFfSQsCojtLBimlgFmkfSpXLVZhnI.jSvoy0EajKukXLtjoHu8457EZdrKwmaDkN41awBXM84
   
   And make it available on your web server at this URL:
   
   http://cms.qialiaokefu.com/.well-known/acme-challenge/2iDo5YYUgez2GsaFfSQsCojtLBimlgFmkfSpXLVZhnI
   ```

6. **使用Alt+K创建验证文件**
   - 在Web界面中按 `Alt + K`
   - 使用指令创建新文件并保存内容：
   ```bash
   echo "2iDo5YYUgez2GsaFfSQsCojtLBimlgFmkfSpXLVZhnI.jSvoy0EajKukXLtjoHu8457EZdrKwmaDkN41awBXM84" > /usr/local/nginx/html/.well-known/acme-challenge/2iDo5YYUgez2GsaFfSQsCojtLBimlgFmkfSpXLVZhnI
   ```
   - 执行指令创建新文件并保存内容

7. **确认续费**
   - 回到Termius终端的续费命令窗口
   - 按 `Enter` 确认继续
   - 等待证书续费完成

#### 步骤4: Web界面恢复配置

9. **切换到Web管理界面**
   - 重新编辑文件: `cms.qialiaokefu.com.conf`

9. **重新注释配置文件**
    - 将之前取消注释的配置重新注释（添加 `#` 号）：
    ```nginx
        # 如果 /.well-known/acme-challenge/ 是静态文件目录的一部分，可以在此处理
        # location /.well-known/acme-challenge/ {
        #     default_type "text/plain";
        #     allow all;
        # }
    ```

10. **保存文件并重启Nginx**
    - 在Web界面中点击保存按钮
    - **按 `Alt + K` 验证配置并重启Nginx**
    ```bash
    # 验证配置文件
    sudo /usr/local/nginx/sbin/nginx -t
    
    # 重启Nginx
    sudo /usr/local/nginx/sbin/nginx -s reload
    ```

---

### 🔌 WebSocket服务器证书 (ws.qialiaokefu.com)

**服务器**: `43.155.10.32`

#### 步骤1: 执行SSL续费指令

**首先执行续费命令**:
```bash
sudo certbot certonly --manual -d ws.qialiaokefu.com
```

**根据系统提示选择处理方式**:

- **如果系统提示选择1或2**: 直接输入 `2` 按回车确认即可，无需其他操作
- **如果系统提示"Press Enter to Continue"**: 需要先完成后续所有步骤（修改配置、重启Nginx、创建验证文件），完成后再回到续费命令窗口按Enter继续

#### 步骤2: Web界面操作（仅在需要手动验证时执行）

1. **登录Web管理界面**
   - 地址: https://43.155.10.32:10000/
   - 账号: `root`
   - 密码: `[待填写]`

2. **通过Web界面编辑配置文件**
   - 在Web管理界面中导航到文件管理器
   - 进入路径: `/usr/local/nginx/conf/vhost/`
   - 找到并编辑文件: `ws.qialiaokefu.com.conf`

3. **取消注释并修改配置**
   - 找到以下注释部分并取消注释（删除 `#` 号）：
   ```nginx
    #location /.well-known/acme-challenge/ {
            #root /usr/local/nginx/html;
            #default_type "text/plain";
            #allow all;
        #}
   ```

4. **保存文件并重启Nginx**
   - 在Web界面中点击保存按钮
   - **按 `Alt + K` 验证配置并重启Nginx**
   ```bash
   # 验证配置文件
   sudo /usr/local/nginx/sbin/nginx -t
   
   # 重启Nginx
   sudo /usr/local/nginx/sbin/nginx -s reload
   ```

#### 步骤3: Web界面创建验证文件

5. **根据SSL续费指令提示创建验证文件**
   - 系统会显示类似以下提示：
   ```
   - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
   Create a file containing just this data:
   
   2iDo5YYUgez2GsaFfSQsCojtLBimlgFmkfSpXLVZhnI.jSvoy0EajKukXLtjoHu8457EZdrKwmaDkN41awBXM84
   
   And make it available on your web server at this URL:
   
   http://ws.qialiaokefu.com/.well-known/acme-challenge/2iDo5YYUgez2GsaFfSQsCojtLBimlgFmkfSpXLVZhnI
   ```

6. **使用Alt+K创建验证文件**
   - 在Web界面中按 `Alt + K`
   - 使用指令创建新文件并保存内容：
   ```bash
   echo "2iDo5YYUgez2GsaFfSQsCojtLBimlgFmkfSpXLVZhnI.jSvoy0EajKukXLtjoHu8457EZdrKwmaDkN41awBXM84" > /usr/local/nginx/html/.well-known/acme-challenge/2iDo5YYUgez2GsaFfSQsCojtLBimlgFmkfSpXLVZhnI
   ```
   - 执行指令创建新文件并保存内容

7. **确认续费**
   - 回到Termius终端的续费命令窗口
   - 按 `Enter` 确认继续
   - 等待证书续费完成

#### 步骤4: Web界面恢复配置

9. **切换到Web管理界面**
   - 重新编辑文件: `ws.qialiaokefu.com.conf`

9. **重新注释配置文件**
    - 将之前取消注释的配置重新注释（添加 `#` 号）：
    ```nginx
        #location /.well-known/acme-challenge/ {
                #root /usr/local/nginx/html;
                #default_type "text/plain";
                #allow all;
            #}
    ```

10. **保存文件并重启Nginx**
    - 在Web界面中点击保存按钮
    - **按 `Alt + K` 验证配置并重启Nginx**
    ```bash
    # 验证配置文件
    sudo /usr/local/nginx/sbin/nginx -t
    
    # 重启Nginx
    sudo /usr/local/nginx/sbin/nginx -s reload
    ```

---

## 🚀 快速操作指南

### 📝 操作流程模板

**Web界面操作**：
```bash
# 1. 登录Web管理界面
# 地址: https://[服务器IP]:10000/
# 账号: root

# 2. 通过Web界面编辑配置文件
# 在Webmin界面左侧菜单栏点击"工具类"
# 在展开的工具类菜单中点击"文件管理器"
# 在文件管理器中导航到路径: /usr/local/nginx/conf/vhost/
# 编辑文件: [域名].conf

# 3. 取消注释以下配置（删除 # 号）
location / {
    root /usr/local/nginx/html;
    index index.html index.htm;
    location /.well-known/acme-challenge/ {
        default_type "text/plain";
        allow all;  
    }
}

# 4. 保存文件并按 Alt + K 验证配置并重启Nginx

# 5. 使用Alt+K创建验证文件
# 按Alt+K，使用指令创建新文件并保存内容：
# echo "2iDo5YYUgez2GsaFfSQsCojtLBimlgFmkfSpXLVZhnI.jSvoy0EajKukXLtjoHu8457EZdrKwmaDkN41awBXM84" > /usr/local/nginx/html/.well-known/acme-challenge/2iDo5YYUgez2GsaFfSQsCojtLBimlgFmkfSpXLVZhnI
```

**Termius终端操作**：
```bash
# 1. 在Termius中执行SSL续费指令
sudo certbot certonly --manual -d [域名]

# 2. 根据系统提示选择处理方式
# - 如果提示选择1或2: 直接输入 2 按回车
# - 如果提示"Press Enter to Continue": 需要先完成Web界面操作

# 3. 完成Web界面操作后，回到续费命令窗口按Enter确认续费
```

### 🔧 验证文件创建模板

```bash
# 1. 进入验证文件目录
cd /usr/local/nginx/html/.well-known/acme-challenge/

# 2. 根据SSL续费指令提示创建文件
# 示例：如果系统提示如下：
# Create a file containing just this data:
# 2iDo5YYUgez2GsaFfSQsCojtLBimlgFmkfSpXLVZhnI.jSvoy0EajKukXLtjoHu8457EZdrKwmaDkN41awBXM84
# And make it available at this URL:
# http://api.qialiaokefu.com/.well-known/acme-challenge/2iDo5YYUgez2GsaFfSQsCojtLBimlgFmkfSpXLVZhnI

# 3. 推荐方式：使用Alt+K直接创建新文件
# 按Alt+K，使用指令创建新文件并保存内容：
# echo "2iDo5YYUgez2GsaFfSQsCojtLBimlgFmkfSpXLVZhnI.jSvoy0EajKukXLtjoHu8457EZdrKwmaDkN41awBXM84" > /usr/local/nginx/html/.well-known/acme-challenge/2iDo5YYUgez2GsaFfSQsCojtLBimlgFmkfSpXLVZhnI

# 4. 备选方式：使用命令行创建新文件并保存内容
echo "2iDo5YYUgez2GsaFfSQsCojtLBimlgFmkfSpXLVZhnI.jSvoy0EajKukXLtjoHu8457EZdrKwmaDkN41awBXM84" > 2iDo5YYUgez2GsaFfSQsCojtLBimlgFmkfSpXLVZhnI

# 注意：旧文件可以保留，不需要删除
```

### 📋 续费命令模板

```bash
# 执行续费命令
sudo certbot certonly --manual -d [域名]

# 根据系统提示选择处理方式：
# - 如果系统提示选择1或2: 直接输入 2 按回车确认即可
# - 如果系统提示"Press Enter to Continue": 需要先完成后续所有步骤，完成后再回到续费命令窗口按Enter继续

# 如果需要手动验证，按以下步骤操作：
# 1. 修改配置文件取消注释
# 2. 重启Nginx
# 3. 创建验证文件
# 4. 回到续费命令窗口按Enter确认续费
# 5. 续费完成后，建议保留验证路径配置（可选注释）
# 6. 如需要，重启Nginx
```

---

## ⚠️ 异常处理

### 🚨 Nginx重启异常

**问题**: 重启Nginx提示PID不存在

**解决方案**:

1. **查找Nginx主进程**
   ```bash
   ps -ef | grep 'nginx: master process' | grep -v grep
   ```

2. **手动写入PID文件**
   ```bash
   # 假设找到的PID是11233
   echo 11233 > /usr/local/nginx/logs/nginx.pid
   ```

3. **重新加载配置**
   ```bash
   sudo /usr/local/nginx/sbin/nginx -s reload
   ```

### 📝 示例输出
```bash
# 查找主进程
root     11233  ...  nginx: master process /usr/local/nginx/sbin/nginx

# 写入PID文件
echo 11233 > /usr/local/nginx/logs/nginx.pid

# 重新加载
sudo /usr/local/nginx/sbin/nginx -s reload
```

---

## 📅 续费时间表

| 域名 | 服务器IP | 到期时间 | 续费状态 |
|------|----------|----------|----------|
| api.qialiaokefu.com | 119.28.68.249 | [待更新] | ⏳ |
| cms.qialiaokefu.com | 43.132.102.59 | [待更新] | ⏳ |
| ws.qialiaokefu.com | 43.155.10.32 | [待更新] | ⏳ |

---

## 🔗 相关链接

- **Let's Encrypt**: https://letsencrypt.org/
- **Certbot文档**: https://certbot.eff.org/
- **Nginx官方文档**: https://nginx.org/en/docs/

---

## 📞 技术支持

- **系统管理员**: [待填写]
- **SSL证书管理**: [待填写]

---

*🔐 SSL证书续费指南*  
*📅 最后更新: 2024年*  
*📋 版本: v1.0*
