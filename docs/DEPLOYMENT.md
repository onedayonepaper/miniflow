# MiniFlow 배포 가이드

이 문서는 MiniFlow를 다양한 환경에 배포하는 방법을 상세히 설명합니다.

---

## 🎯 권장 배포 방식

| 방식 | 월 비용 | 난이도 | 권장 대상 |
|------|--------|--------|----------|
| **VPS + Docker** ⭐ | $12~15 | ⭐⭐ | 소규모~중규모 (권장) |
| 일반 서버 | $6~12 | ⭐⭐⭐ | 직접 관리 선호 |
| AWS | $100~200+ | ⭐⭐⭐⭐ | 대규모, 엔터프라이즈 |

> **💡 권장**: VPS + Docker 방식이 가장 가성비 좋고 관리하기 쉽습니다.

---

## 목차

1. [사전 요구사항](#1-사전-요구사항)
2. [로컬 개발 환경 설정](#2-로컬-개발-환경-설정)
3. [VPS 배포 (권장)](#3-vps-배포-권장)
4. [Docker를 이용한 배포](#4-docker를-이용한-배포)
5. [일반 서버 배포 (Ubuntu/CentOS)](#5-일반-서버-배포)
6. [AWS 배포 (대규모용)](#6-aws-배포-대규모용)
7. [환경 변수 설정](#7-환경-변수-설정)
8. [데이터베이스 설정](#8-데이터베이스-설정)
9. [큐 워커 설정](#9-큐-워커-설정)
10. [스케줄러 설정](#10-스케줄러-설정)
11. [Nginx 설정](#11-nginx-설정)
12. [SSL/HTTPS 설정](#12-sslhttps-설정)
13. [모니터링 및 로깅](#13-모니터링-및-로깅)
14. [백업 전략](#14-백업-전략)
15. [트러블슈팅](#15-트러블슈팅)

---

## 1. 사전 요구사항

### 최소 시스템 요구사항

| 항목 | 개발 환경 | 운영 환경 (소규모) | 운영 환경 (중규모) |
|------|----------|-------------------|-------------------|
| CPU | 2 Core | 2 Core | 4 Core |
| RAM | 2 GB | 4 GB | 8 GB |
| Storage | 10 GB | 50 GB SSD | 100 GB SSD |
| OS | macOS/Linux/WSL2 | Ubuntu 22.04 LTS | Ubuntu 22.04 LTS |

### 필수 소프트웨어

```bash
# 버전 확인
php -v          # PHP 8.2 이상
composer -V     # Composer 2.x
node -v         # Node.js 18+ (프론트엔드 빌드시)
mysql --version # MySQL 8.0+
redis-cli -v    # Redis 6.0+
```

---

## 2. 로컬 개발 환경 설정

### 2.1 저장소 클론

```bash
git clone https://github.com/your-org/miniflow.git
cd miniflow
```

### 2.2 의존성 설치

```bash
# PHP 의존성
composer install

# 프론트엔드 의존성 (있는 경우)
npm install
npm run build
```

### 2.3 환경 설정

```bash
# .env 파일 생성
cp .env.example .env

# 애플리케이션 키 생성
php artisan key:generate

# 심볼릭 링크 생성 (첨부파일용)
php artisan storage:link
```

### 2.4 데이터베이스 설정

```bash
# MySQL에서 데이터베이스 생성
mysql -u root -p
CREATE DATABASE miniflow CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'miniflow'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON miniflow.* TO 'miniflow'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# .env 파일에서 DB 설정 수정
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=miniflow
DB_USERNAME=miniflow
DB_PASSWORD=your_password
```

### 2.5 마이그레이션 및 시드

```bash
# 테이블 생성
php artisan migrate

# 초기 데이터 삽입 (선택)
php artisan db:seed

# 또는 한 번에
php artisan migrate --seed
```

### 2.6 개발 서버 실행

```bash
# Laravel 개발 서버
php artisan serve

# 큐 워커 (별도 터미널)
php artisan queue:work

# 접속
open http://localhost:8000
```

---

## 3. VPS 배포 (권장)

소규모~중규모 서비스에 가장 적합한 배포 방식입니다.

### 3.1 VPS 업체 선택

| 업체 | 서울 리전 | 추천 플랜 | 월 비용 | 특징 |
|------|----------|----------|--------|------|
| **Vultr** ⭐ | ✅ 있음 | 2 vCPU, 2GB RAM | $12 | 한국 사용자 최적 |
| Linode | ❌ (도쿄) | 2 vCPU, 2GB RAM | $12 | 안정적 |
| DigitalOcean | ❌ (싱가포르) | 2 vCPU, 2GB RAM | $12 | 문서 풍부 |

> **권장**: 한국 사용자 대상이면 **Vultr 서울 리전** 선택

### 3.2 VPS 생성

1. [Vultr](https://vultr.com) 또는 선호 업체 가입
2. 서버 생성:
   - **Location**: Seoul (또는 Tokyo)
   - **OS**: Ubuntu 22.04 LTS
   - **Plan**: 2 vCPU, 2GB RAM, 50GB SSD ($12/월)
3. SSH 키 등록 (권장)

### 3.3 서버 초기 설정

```bash
# SSH 접속
ssh root@your-server-ip

# 시스템 업데이트
apt update && apt upgrade -y

# 기본 패키지 설치
apt install -y curl git ufw

# Docker 설치 (공식 스크립트)
curl -fsSL https://get.docker.com | sh

# Docker Compose 플러그인 설치
apt install -y docker-compose-plugin

# Docker 서비스 시작
systemctl enable docker
systemctl start docker

# 방화벽 설정
ufw allow 22    # SSH
ufw allow 80    # HTTP
ufw allow 443   # HTTPS
ufw --force enable
```

### 3.4 프로젝트 배포

```bash
# 배포 디렉토리 생성
mkdir -p /var/www
cd /var/www

# 소스 클론
git clone https://github.com/your-org/miniflow.git
cd miniflow

# 환경 변수 설정
cp .env.production.example .env
nano .env  # 실제 값으로 수정

# 필수 수정 항목:
# - APP_URL=https://your-domain.com
# - DB_PASSWORD=secure_password
# - DB_ROOT_PASSWORD=secure_root_password
```

### 3.5 Docker로 실행

```bash
# 프로덕션 빌드 및 실행
docker compose -f docker-compose.prod.yml up -d --build

# 마이그레이션 실행
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force

# 초기 데이터 시드 (선택)
docker compose -f docker-compose.prod.yml exec app php artisan db:seed --force

# 상태 확인
docker compose -f docker-compose.prod.yml ps

# 로그 확인
docker compose -f docker-compose.prod.yml logs -f
```

### 3.6 도메인 및 SSL 설정

```bash
# Certbot 설치
apt install -y certbot

# Nginx 중지 (포트 80 해제)
docker compose -f docker-compose.prod.yml stop nginx

# SSL 인증서 발급
certbot certonly --standalone -d your-domain.com

# 인증서 복사
mkdir -p /var/www/miniflow/ssl
cp /etc/letsencrypt/live/your-domain.com/fullchain.pem /var/www/miniflow/ssl/
cp /etc/letsencrypt/live/your-domain.com/privkey.pem /var/www/miniflow/ssl/
chmod 600 /var/www/miniflow/ssl/*.pem

# Nginx 재시작
docker compose -f docker-compose.prod.yml up -d nginx

# 인증서 자동 갱신 설정
echo "0 3 * * * certbot renew --pre-hook 'docker compose -f /var/www/miniflow/docker-compose.prod.yml stop nginx' --post-hook 'cp /etc/letsencrypt/live/your-domain.com/*.pem /var/www/miniflow/ssl/ && docker compose -f /var/www/miniflow/docker-compose.prod.yml up -d nginx'" | crontab -
```

### 3.7 배포 자동화 스크립트

`/var/www/miniflow/deploy.sh` 파일 생성:

```bash
#!/bin/bash
set -e

cd /var/www/miniflow

echo "📥 최신 코드 가져오기..."
git pull origin main

echo "🔨 Docker 이미지 빌드..."
docker compose -f docker-compose.prod.yml build

echo "🚀 서비스 재시작..."
docker compose -f docker-compose.prod.yml up -d

echo "📦 마이그레이션 실행..."
docker compose -f docker-compose.prod.yml exec -T app php artisan migrate --force

echo "🧹 캐시 갱신..."
docker compose -f docker-compose.prod.yml exec -T app php artisan config:cache
docker compose -f docker-compose.prod.yml exec -T app php artisan route:cache
docker compose -f docker-compose.prod.yml exec -T app php artisan view:cache

echo "✅ 배포 완료!"
docker compose -f docker-compose.prod.yml ps
```

```bash
# 실행 권한 부여
chmod +x /var/www/miniflow/deploy.sh

# 배포 실행
./deploy.sh
```

### 3.8 VPS 비용 요약

| 항목 | 월 비용 |
|------|--------|
| VPS (Vultr 2GB) | $12 |
| 도메인 (선택) | ~$1 |
| SSL (Let's Encrypt) | 무료 |
| **합계** | **~$13/월** |

> AWS 대비 **80-90% 비용 절감**

---

## 4. Docker를 이용한 배포

### 4.1 Docker Compose (개발/테스트)

```bash
# 컨테이너 빌드 및 실행
docker compose up -d

# 로그 확인
docker compose logs -f

# 마이그레이션
docker compose exec app php artisan migrate --seed

# 접속
open http://localhost:8080
```

### 4.2 프로덕션용 Docker Compose

`docker-compose.prod.yml` 파일 생성:

```yaml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile.prod
    restart: always
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
    volumes:
      - ./storage:/var/www/html/storage
      - ./.env:/var/www/html/.env:ro
    depends_on:
      - db
      - redis
    networks:
      - miniflow

  nginx:
    image: nginx:alpine
    restart: always
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./nginx.conf:/etc/nginx/nginx.conf:ro
      - ./public:/var/www/html/public:ro
      - ./ssl:/etc/nginx/ssl:ro
    depends_on:
      - app
    networks:
      - miniflow

  db:
    image: mysql:8.0
    restart: always
    environment:
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD}
      MYSQL_DATABASE: ${DB_DATABASE}
      MYSQL_USER: ${DB_USERNAME}
      MYSQL_PASSWORD: ${DB_PASSWORD}
    volumes:
      - mysql_data:/var/lib/mysql
    networks:
      - miniflow

  redis:
    image: redis:7-alpine
    restart: always
    command: redis-server --appendonly yes
    volumes:
      - redis_data:/data
    networks:
      - miniflow

  queue:
    build:
      context: .
      dockerfile: Dockerfile.prod
    restart: always
    command: php artisan queue:work --sleep=3 --tries=3 --max-time=3600
    environment:
      - APP_ENV=production
    volumes:
      - ./storage:/var/www/html/storage
      - ./.env:/var/www/html/.env:ro
    depends_on:
      - db
      - redis
    networks:
      - miniflow

  scheduler:
    build:
      context: .
      dockerfile: Dockerfile.prod
    restart: always
    command: sh -c "while true; do php artisan schedule:run --verbose --no-interaction; sleep 60; done"
    environment:
      - APP_ENV=production
    volumes:
      - ./storage:/var/www/html/storage
      - ./.env:/var/www/html/.env:ro
    depends_on:
      - db
      - redis
    networks:
      - miniflow

volumes:
  mysql_data:
  redis_data:

networks:
  miniflow:
    driver: bridge
```

### 4.3 프로덕션 Dockerfile

`Dockerfile.prod` 파일 생성:

```dockerfile
FROM php:8.2-fpm-alpine

# 시스템 의존성
RUN apk add --no-cache \
    nginx \
    supervisor \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    zip \
    libzip-dev \
    icu-dev

# PHP 확장
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        gd \
        bcmath \
        opcache \
        intl \
        zip \
        pcntl

# Redis 확장
RUN pecl install redis && docker-php-ext-enable redis

# Composer 설치
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 작업 디렉토리
WORKDIR /var/www/html

# 소스 복사
COPY . .

# Composer 의존성 설치 (개발 의존성 제외)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# 권한 설정
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# PHP 최적화
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

EXPOSE 9000
CMD ["php-fpm"]
```

### 4.4 Docker 프로덕션 배포 명령

```bash
# 프로덕션 환경 설정
cp .env.production.example .env
# .env 파일 수정 (실제 값으로)

# 빌드 및 실행
docker compose -f docker-compose.prod.yml up -d --build

# 마이그레이션
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force

# 상태 확인
docker compose -f docker-compose.prod.yml ps
```

---

## 5. 일반 서버 배포

### 5.1 Ubuntu 22.04 서버 준비

```bash
# 시스템 업데이트
sudo apt update && sudo apt upgrade -y

# 필수 패키지 설치
sudo apt install -y \
    software-properties-common \
    curl \
    git \
    unzip \
    supervisor \
    cron
```

### 5.2 PHP 8.2 설치

```bash
# PHP 저장소 추가
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# PHP 및 확장 설치
sudo apt install -y \
    php8.2-fpm \
    php8.2-cli \
    php8.2-mysql \
    php8.2-pgsql \
    php8.2-redis \
    php8.2-curl \
    php8.2-gd \
    php8.2-mbstring \
    php8.2-xml \
    php8.2-zip \
    php8.2-bcmath \
    php8.2-intl \
    php8.2-opcache

# PHP-FPM 시작
sudo systemctl enable php8.2-fpm
sudo systemctl start php8.2-fpm
```

### 5.3 MySQL 8.0 설치

```bash
# MySQL 설치
sudo apt install -y mysql-server

# 보안 설정
sudo mysql_secure_installation

# 데이터베이스 및 사용자 생성
sudo mysql -e "CREATE DATABASE miniflow CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER 'miniflow'@'localhost' IDENTIFIED BY 'your_secure_password';"
sudo mysql -e "GRANT ALL PRIVILEGES ON miniflow.* TO 'miniflow'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"
```

### 5.4 Redis 설치

```bash
sudo apt install -y redis-server

# 설정 수정 (비밀번호 설정 권장)
sudo nano /etc/redis/redis.conf
# requirepass your_redis_password

sudo systemctl enable redis-server
sudo systemctl restart redis-server
```

### 5.5 Nginx 설치

```bash
sudo apt install -y nginx
sudo systemctl enable nginx
```

### 5.6 Composer 설치

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 5.7 애플리케이션 배포

```bash
# 배포 디렉토리 생성
sudo mkdir -p /var/www/miniflow
sudo chown -R $USER:www-data /var/www/miniflow

# 소스 클론
cd /var/www/miniflow
git clone https://github.com/your-org/miniflow.git .

# 의존성 설치
composer install --no-dev --optimize-autoloader

# 환경 설정
cp .env.production.example .env
nano .env  # 실제 값으로 수정

# 키 생성
php artisan key:generate

# 스토리지 링크
php artisan storage:link

# 권한 설정
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# 마이그레이션
php artisan migrate --force

# 캐시 최적화
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 6. AWS 배포 (대규모용)

> ⚠️ **참고**: AWS는 대규모 엔터프라이즈 환경에 적합합니다.
> 소규모~중규모 서비스는 [3. VPS 배포](#3-vps-배포-권장)를 권장합니다.
> **예상 비용**: $100~200+/월 (EC2 + RDS + ElastiCache + S3 + ALB)

### 6.1 EC2 인스턴스 설정

```bash
# 인스턴스 타입: t3.small 이상 권장
# AMI: Ubuntu 22.04 LTS
# 보안 그룹:
#   - SSH (22): 관리자 IP만
#   - HTTP (80): 0.0.0.0/0
#   - HTTPS (443): 0.0.0.0/0
```

### 6.2 RDS (MySQL) 설정

```bash
# RDS 인스턴스 생성
# - 엔진: MySQL 8.0
# - 인스턴스 클래스: db.t3.micro (테스트) / db.t3.small (프로덕션)
# - 스토리지: 20GB gp2 SSD

# .env에서 RDS 엔드포인트 설정
DB_HOST=your-rds-endpoint.region.rds.amazonaws.com
DB_PORT=3306
DB_DATABASE=miniflow
DB_USERNAME=admin
DB_PASSWORD=your_rds_password
```

### 6.3 ElastiCache (Redis) 설정

```bash
# ElastiCache 클러스터 생성
# - 엔진: Redis 7.x
# - 노드 타입: cache.t3.micro

# .env에서 Redis 설정
REDIS_HOST=your-elasticache-endpoint.region.cache.amazonaws.com
REDIS_PORT=6379
```

### 6.4 S3 (파일 스토리지) 설정

```bash
# S3 버킷 생성 (예: miniflow-attachments)

# IAM 정책 생성 및 사용자 연결
# .env에서 S3 설정
AWS_ACCESS_KEY_ID=your_access_key
AWS_SECRET_ACCESS_KEY=your_secret_key
AWS_DEFAULT_REGION=ap-northeast-2
AWS_BUCKET=miniflow-attachments
FILESYSTEM_DISK=s3
```

### 6.5 ALB (로드 밸런서) 설정 (선택)

```bash
# Application Load Balancer 생성
# - 타겟 그룹: EC2 인스턴스
# - Health Check: /api/health
# - HTTPS 리스너: ACM 인증서 연결
```

---

## 7. 환경 변수 설정

### 7.1 필수 환경 변수

```bash
# 애플리케이션 기본 설정
APP_NAME=MiniFlow
APP_ENV=production
APP_KEY=base64:your_generated_key
APP_DEBUG=false
APP_TIMEZONE=Asia/Seoul
APP_URL=https://your-domain.com

# 데이터베이스
DB_CONNECTION=mysql
DB_HOST=your_db_host
DB_PORT=3306
DB_DATABASE=miniflow
DB_USERNAME=miniflow
DB_PASSWORD=secure_password

# Redis
REDIS_HOST=your_redis_host
REDIS_PORT=6379
REDIS_PASSWORD=null

# 캐시 & 세션 & 큐
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# 메일 설정
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="${APP_NAME}"
```

### 7.2 환경 변수 보안

```bash
# .env 파일 권한 설정
chmod 600 .env

# 소스 컨트롤에서 제외 확인
cat .gitignore | grep .env
# .env
# .env.*
# !.env.example
# !.env.production.example
```

---

## 8. 데이터베이스 설정

### 8.1 마이그레이션 실행

```bash
# 프로덕션 마이그레이션 (확인 프롬프트 건너뛰기)
php artisan migrate --force

# 롤백 (주의!)
php artisan migrate:rollback --step=1 --force
```

### 8.2 시드 데이터 (선택)

```bash
# 기본 역할 및 권한만 시드 (프로덕션용)
php artisan db:seed --class=RoleAndPermissionSeeder --force

# 테스트 데이터 시드 (개발용만!)
# php artisan db:seed --force
```

### 8.3 데이터베이스 최적화

```sql
-- MySQL 설정 최적화 (/etc/mysql/mysql.conf.d/mysqld.cnf)
[mysqld]
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT
max_connections = 200
```

---

## 9. 큐 워커 설정

### 9.1 Supervisor 설정

```bash
# Supervisor 설정 파일 생성
sudo nano /etc/supervisor/conf.d/miniflow-worker.conf
```

```ini
[program:miniflow-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/miniflow/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/miniflow/storage/logs/worker.log
stopwaitsecs=3600
```

### 9.2 Supervisor 명령

```bash
# 설정 리로드
sudo supervisorctl reread
sudo supervisorctl update

# 워커 상태 확인
sudo supervisorctl status miniflow-worker:*

# 워커 재시작
sudo supervisorctl restart miniflow-worker:*

# 전체 재시작
sudo supervisorctl restart all
```

### 9.3 큐 모니터링

```bash
# 큐 상태 확인
php artisan queue:monitor redis:default

# 실패한 작업 확인
php artisan queue:failed

# 실패한 작업 재시도
php artisan queue:retry all

# 실패한 작업 삭제
php artisan queue:flush
```

---

## 10. 스케줄러 설정

### 10.1 Cron 설정

```bash
# crontab 편집
sudo crontab -e -u www-data

# 다음 줄 추가
* * * * * cd /var/www/miniflow && php artisan schedule:run >> /dev/null 2>&1
```

### 10.2 스케줄 작업 확인

```bash
# 등록된 스케줄 작업 목록
php artisan schedule:list

# 스케줄 테스트 실행
php artisan schedule:test
```

---

## 11. Nginx 설정

### 11.1 사이트 설정 파일

```bash
sudo nano /etc/nginx/sites-available/miniflow
```

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name your-domain.com;

    # HTTP to HTTPS 리다이렉트
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name your-domain.com;

    root /var/www/miniflow/public;
    index index.php;

    # SSL 설정
    ssl_certificate /etc/letsencrypt/live/your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256;
    ssl_prefer_server_ciphers off;

    # 보안 헤더
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # 로그
    access_log /var/log/nginx/miniflow-access.log;
    error_log /var/log/nginx/miniflow-error.log;

    # 파일 업로드 크기 제한
    client_max_body_size 10M;

    # Gzip 압축
    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types text/plain text/css text/xml application/json application/javascript application/rss+xml application/atom+xml image/svg+xml;

    # 정적 파일 캐싱
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff|woff2)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    # Laravel 라우팅
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    # 숨김 파일 차단
    location ~ /\.(?!well-known) {
        deny all;
    }
}
```

### 11.2 사이트 활성화

```bash
# 심볼릭 링크 생성
sudo ln -s /etc/nginx/sites-available/miniflow /etc/nginx/sites-enabled/

# 기본 사이트 비활성화
sudo rm /etc/nginx/sites-enabled/default

# 설정 테스트
sudo nginx -t

# Nginx 재시작
sudo systemctl restart nginx
```

---

## 12. SSL/HTTPS 설정

### 12.1 Let's Encrypt 인증서 발급

```bash
# Certbot 설치
sudo apt install -y certbot python3-certbot-nginx

# 인증서 발급
sudo certbot --nginx -d your-domain.com -d www.your-domain.com

# 자동 갱신 테스트
sudo certbot renew --dry-run
```

### 12.2 인증서 자동 갱신 확인

```bash
# Certbot 타이머 확인
sudo systemctl status certbot.timer

# 수동 갱신 (필요시)
sudo certbot renew
```

---

## 13. 모니터링 및 로깅

### 13.1 애플리케이션 로그

```bash
# 실시간 로그 확인
tail -f /var/www/miniflow/storage/logs/laravel.log

# 에러 로그만 확인
grep -i error /var/www/miniflow/storage/logs/laravel.log

# 로그 로테이션 설정
sudo nano /etc/logrotate.d/miniflow
```

```
/var/www/miniflow/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    sharedscripts
    postrotate
        systemctl reload php8.2-fpm
    endscript
}
```

### 13.2 Health Check 모니터링

```bash
# Health Check 엔드포인트 테스트
curl -s https://your-domain.com/api/health | jq

# 예상 응답:
# {
#   "status": "healthy",
#   "timestamp": "2024-01-15T10:30:00+09:00",
#   "version": "1.0.0",
#   "services": {
#     "database": "ok",
#     "cache": "ok",
#     "queue": "ok"
#   }
# }
```

### 13.3 외부 모니터링 서비스 연동 (선택)

```bash
# Sentry (에러 추적)
composer require sentry/sentry-laravel
php artisan sentry:publish --dsn=your_sentry_dsn

# .env 설정
SENTRY_LARAVEL_DSN=https://xxxxx@sentry.io/xxxxx
SENTRY_TRACES_SAMPLE_RATE=0.1
```

### 13.4 서버 모니터링

```bash
# 디스크 사용량 확인
df -h

# 메모리 확인
free -m

# CPU 확인
top -bn1 | head -20

# 프로세스 확인
ps aux | grep php
ps aux | grep nginx
ps aux | grep mysql
```

---

## 14. 백업 전략

### 14.1 데이터베이스 백업 스크립트

```bash
# 백업 스크립트 생성
sudo nano /usr/local/bin/backup-miniflow.sh
```

```bash
#!/bin/bash

# 설정
BACKUP_DIR="/var/backups/miniflow"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="miniflow"
DB_USER="miniflow"
DB_PASS="your_password"
RETENTION_DAYS=7

# 디렉토리 생성
mkdir -p $BACKUP_DIR

# 데이터베이스 백업
mysqldump -u$DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# 업로드 파일 백업
tar -czf $BACKUP_DIR/storage_$DATE.tar.gz -C /var/www/miniflow storage/app

# 오래된 백업 삭제
find $BACKUP_DIR -type f -mtime +$RETENTION_DAYS -delete

# 결과 로그
echo "[$DATE] Backup completed" >> /var/log/miniflow-backup.log
```

```bash
# 실행 권한 부여
sudo chmod +x /usr/local/bin/backup-miniflow.sh

# Cron 등록 (매일 새벽 3시)
echo "0 3 * * * root /usr/local/bin/backup-miniflow.sh" | sudo tee /etc/cron.d/miniflow-backup
```

### 14.2 S3 백업 (AWS 사용시)

```bash
# AWS CLI 설치
sudo apt install -y awscli

# 백업 스크립트에 S3 업로드 추가
aws s3 cp $BACKUP_DIR/db_$DATE.sql.gz s3://your-backup-bucket/miniflow/db/
aws s3 cp $BACKUP_DIR/storage_$DATE.tar.gz s3://your-backup-bucket/miniflow/storage/
```

---

## 15. 트러블슈팅

### 15.1 일반적인 문제

#### 500 Internal Server Error

```bash
# 로그 확인
tail -f /var/www/miniflow/storage/logs/laravel.log

# 권한 확인
ls -la /var/www/miniflow/storage
ls -la /var/www/miniflow/bootstrap/cache

# 권한 수정
sudo chown -R www-data:www-data /var/www/miniflow/storage
sudo chown -R www-data:www-data /var/www/miniflow/bootstrap/cache
sudo chmod -R 775 /var/www/miniflow/storage
sudo chmod -R 775 /var/www/miniflow/bootstrap/cache
```

#### 캐시 문제

```bash
# 모든 캐시 클리어
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 프로덕션에서 캐시 재생성
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### 큐 작업 실패

```bash
# 실패한 작업 확인
php artisan queue:failed

# 특정 작업 재시도
php artisan queue:retry [job-id]

# 모든 실패 작업 재시도
php artisan queue:retry all

# 큐 워커 재시작
sudo supervisorctl restart miniflow-worker:*
```

#### 데이터베이스 연결 오류

```bash
# MySQL 상태 확인
sudo systemctl status mysql

# 연결 테스트
mysql -u miniflow -p -h localhost miniflow

# PHP에서 PDO 확인
php -m | grep pdo
```

#### 메일 발송 실패

```bash
# 메일 설정 테스트
php artisan tinker
>>> Mail::raw('Test email', function($message) { $message->to('test@example.com')->subject('Test'); });

# 큐에서 메일 처리 확인
php artisan queue:work --once
```

### 15.2 성능 최적화

```bash
# PHP OPcache 활성화 확인
php -i | grep opcache.enable

# Redis 연결 확인
redis-cli ping

# MySQL 슬로우 쿼리 확인
sudo tail -f /var/log/mysql/mysql-slow.log
```

### 15.3 보안 점검

```bash
# .env 파일 노출 확인
curl -I https://your-domain.com/.env
# 403 또는 404 응답이어야 함

# 디버그 모드 확인
curl https://your-domain.com/api/health
# APP_DEBUG=false 여부 확인

# SSL 인증서 확인
openssl s_client -connect your-domain.com:443 -servername your-domain.com
```

---

## 배포 체크리스트

배포 전 아래 항목을 확인하세요:

- [ ] `.env` 파일 설정 완료 (`APP_ENV=production`, `APP_DEBUG=false`)
- [ ] `APP_KEY` 생성됨
- [ ] 데이터베이스 마이그레이션 완료
- [ ] 스토리지 심볼릭 링크 생성됨
- [ ] 파일/폴더 권한 설정됨 (storage, bootstrap/cache)
- [ ] Nginx 설정 완료 및 테스트됨
- [ ] SSL 인증서 설치됨
- [ ] 큐 워커 (Supervisor) 실행 중
- [ ] 스케줄러 (Cron) 설정됨
- [ ] Health Check 엔드포인트 정상 응답
- [ ] 백업 스크립트 설정됨
- [ ] 로그 로테이션 설정됨
- [ ] 보안 헤더 확인됨

---

## 도움말

문제가 발생하면 다음을 참조하세요:

- [Laravel 공식 문서](https://laravel.com/docs)
- [MiniFlow Issues](https://github.com/your-org/miniflow/issues)
- 이메일: support@miniflow.example.com
