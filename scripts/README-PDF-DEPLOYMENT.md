# 한글 PDF 내보내기 실서버 배포 가이드

이 가이드는 Laravel 애플리케이션에서 한글 PDF 내보내기 기능을 실서버에 배포하는 방법을 설명합니다.

## 📋 목차

1. [시스템 요구사항](#시스템-요구사항)
2. [자동 설치 스크립트](#자동-설치-스크립트)
3. [수동 설치 방법](#수동-설치-방법)
4. [Docker 환경 설정](#docker-환경-설정)
5. [테스트 및 검증](#테스트-및-검증)
6. [문제 해결](#문제-해결)
7. [성능 최적화](#성능-최적화)

## 🔧 시스템 요구사항

### 필수 요구사항
- **OS**: Ubuntu 18.04+ / CentOS 7+ / Amazon Linux 2+
- **PHP**: 8.2+
- **메모리**: 최소 2GB (PDF 생성 시 추가 메모리 필요)
- **디스크**: 여유 공간 1GB+

### 지원하는 환경
- ✅ Ubuntu/Debian (apt 패키지 관리자)
- ✅ CentOS/RHEL/AlmaLinux (yum/dnf 패키지 관리자)
- ✅ Docker 환경
- ❌ Windows 서버 (공식 지원 없음)
- ⚠️ ARM64 아키텍처 (별도 컴파일 필요)

## 🚀 자동 설치 스크립트

### 1단계: wkhtmltopdf 설치

```bash
# 스크립트 실행 권한 부여
chmod +x scripts/install-wkhtmltopdf.sh

# wkhtmltopdf 및 한글 폰트 설치
sudo ./scripts/install-wkhtmltopdf.sh
```

### 2단계: Laravel 프로젝트 설정

```bash
# Laravel PDF 설정 자동화
chmod +x scripts/configure-laravel-pdf.sh
./scripts/configure-laravel-pdf.sh
```

### 3단계: 테스트

```bash
# 테스트 라우트 추가 (routes/web.php)
# 스크립트 실행 후 안내에 따라 수동 추가

# 브라우저에서 테스트
# http://your-domain.com/test/korean-pdf
```

## 🛠️ 수동 설치 방법

자동 스크립트를 사용할 수 없는 환경에서의 수동 설치 방법입니다.

### 1. wkhtmltopdf 설치

#### Ubuntu/Debian
```bash
# 시스템 업데이트
sudo apt-get update

# 의존성 설치
sudo apt-get install -y wget fontconfig libfontconfig1 \
    libjpeg-turbo8 libpng16-16 libssl3 libx11-6 libxcb1 \
    libxext6 libxrender1 xfonts-75dpi xfonts-base

# 한글 폰트 설치
sudo apt-get install -y fonts-noto-cjk fonts-nanum

# wkhtmltopdf 다운로드 및 설치
wget https://github.com/wkhtmltopdf/packaging/releases/download/0.12.6.1-2/wkhtmltox_0.12.6.1-2.jammy_amd64.deb
sudo dpkg -i wkhtmltox_0.12.6.1-2.jammy_amd64.deb
sudo apt-get install -f -y
rm wkhtmltox_0.12.6.1-2.jammy_amd64.deb

# 심볼릭 링크 생성
sudo ln -sf /usr/local/bin/wkhtmltopdf /usr/local/bin/wkhtmltopdf
```

#### CentOS/RHEL/AlmaLinux
```bash
# 시스템 업데이트
sudo yum update -y  # 또는 dnf update -y

# 의존성 설치
sudo yum install -y wget fontconfig libjpeg-turbo libpng \
    openssl-libs libX11 libXext libXrender xorg-x11-fonts-75dpi \
    xorg-x11-fonts-Type1

# 한글 폰트 설치
sudo yum install -y google-noto-cjk-fonts  # 또는 dnf install

# wkhtmltopdf 다운로드 및 설치
wget https://github.com/wkhtmltopdf/packaging/releases/download/0.12.6.1-2/wkhtmltox-0.12.6.1-2.almalinux9.x86_64.rpm
sudo yum localinstall -y wkhtmltox-0.12.6.1-2.almalinux9.x86_64.rpm
rm wkhtmltox-0.12.6.1-2.almalinux9.x86_64.rpm

# 심볼릭 링크 생성
sudo ln -sf /usr/local/bin/wkhtmltopdf /usr/local/bin/wkhtmltopdf
```

### 2. Laravel 프로젝트 설정

```bash
# Composer 패키지 설치
composer require knplabs/knp-snappy

# 환경변수 설정 (.env 파일에 추가)
echo "WKHTMLTOPDF_PATH=/usr/local/bin/wkhtmltopdf" >> .env
echo "WKHTMLTOIMAGE_PATH=/usr/local/bin/wkhtmltoimage" >> .env

# 권한 설정
sudo chown www-data:www-data /usr/local/bin/wkhtmltopdf
sudo chmod 755 /usr/local/bin/wkhtmltopdf

# Laravel 캐시 클리어
php artisan config:clear
php artisan cache:clear
```

## 🐳 Docker 환경 설정

### Dockerfile 사용

```bash
# 제공된 Dockerfile 사용
docker build -f scripts/Dockerfile.wkhtmltopdf -t laravel-korean-pdf .

# 컨테이너 실행
docker run -d --name laravel-app \
    -p 9000:9000 \
    -v $(pwd):/var/www \
    laravel-korean-pdf
```

### Docker Compose

```yaml
# docker-compose.yml에 추가
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: scripts/Dockerfile.wkhtmltopdf
    volumes:
      - .:/var/www
    environment:
      - WKHTMLTOPDF_PATH=/usr/local/bin/wkhtmltopdf
    depends_on:
      - mysql
      - redis
```

### 기존 Docker 이미지 수정

```dockerfile
# 기존 Dockerfile에 추가
RUN apt-get update && apt-get install -y \
    wget fontconfig fonts-noto-cjk fonts-nanum

RUN wget -q https://github.com/wkhtmltopdf/packaging/releases/download/0.12.6.1-2/wkhtmltox_0.12.6.1-2.jammy_amd64.deb \
    && dpkg -i wkhtmltox_0.12.6.1-2.jammy_amd64.deb || apt-get install -f -y \
    && rm wkhtmltox_0.12.6.1-2.jammy_amd64.deb \
    && fc-cache -fv
```

## ✅ 테스트 및 검증

### 1. 기본 테스트

```bash
# wkhtmltopdf 설치 확인
wkhtmltopdf --version

# 한글 폰트 확인
fc-list | grep -i "nanum\|noto.*cjk"
```

### 2. Laravel 테스트

```php
// 테스트 라우트 추가 (routes/web.php)
use App\Http\Controllers\PdfTestController;

Route::get('/test/korean-pdf', [PdfTestController::class, 'testKoreanPdf'])
    ->name('test.korean-pdf');
```

### 3. 브라우저 테스트

```
http://your-domain.com/test/korean-pdf
```

성공 시 한글이 포함된 PDF가 다운로드됩니다.

### 4. 프로그래밍 테스트

```php
// artisan tinker에서 실행
use Knp\Snappy\Pdf;

$pdf = new Pdf('/usr/local/bin/wkhtmltopdf');
$html = '<html><body><h1>한글 테스트</h1><p>안녕하세요!</p></body></html>';
$output = $pdf->getOutputFromHtml($html);

echo "PDF Size: " . strlen($output) . " bytes\n";
// 10KB 이상이면 성공
```

## 🚨 문제 해결

### 일반적인 문제들

#### 1. "wkhtmltopdf: not found" 오류

```bash
# 경로 확인
which wkhtmltopdf

# 심볼릭 링크 재생성
sudo ln -sf /usr/local/bin/wkhtmltopdf /usr/local/bin/wkhtmltopdf

# 환경변수 확인
echo $WKHTMLTOPDF_PATH
```

#### 2. 권한 오류

```bash
# 실행 권한 부여
sudo chmod +x /usr/local/bin/wkhtmltopdf

# 웹서버 사용자 확인
ps aux | grep -E "(apache|nginx|www-data)"

# 권한 설정
sudo chown root:www-data /usr/local/bin/wkhtmltopdf
```

#### 3. 한글 폰트가 표시되지 않음

```bash
# 한글 폰트 설치 확인
fc-list | grep -i korean

# 폰트 캐시 업데이트
sudo fc-cache -fv

# 폰트 권한 확인
ls -la /usr/share/fonts/truetype/nanum/
```

#### 4. 메모리 부족 오류

```bash
# PHP 메모리 제한 확인
php -i | grep memory_limit

# php.ini 수정
memory_limit = 512M
max_execution_time = 300
```

#### 5. SELinux 문제 (CentOS/RHEL)

```bash
# SELinux 상태 확인
sestatus

# 임시 비활성화
sudo setenforce 0

# 영구 설정 (재부팅 필요)
sudo sed -i 's/SELINUX=enforcing/SELINUX=disabled/' /etc/selinux/config
```

### 로그 확인

```bash
# Laravel 로그
tail -f storage/logs/laravel.log

# 시스템 로그
sudo tail -f /var/log/syslog

# PHP-FPM 로그
sudo tail -f /var/log/php8.2-fpm.log
```

## ⚡ 성능 최적화

### 1. 메모리 최적화

```php
// config/snappy.php
'options' => [
    'lowquality' => true,  // 품질 낮춤으로 메모리 절약
    'disable-javascript' => true,  // JS 비활성화
    'no-images' => false,  // 이미지 포함 여부
    'disable-smart-shrinking' => true,
],
```

### 2. 캐싱 전략

```php
// PDF 캐싱 구현 예시
use Illuminate\Support\Facades\Cache;

public function exportPdf($id)
{
    $cacheKey = "pdf_analysis_{$id}";

    return Cache::remember($cacheKey, 3600, function() use ($id) {
        // PDF 생성 로직
        return $this->generatePdf($id);
    });
}
```

### 3. 큐 처리

```php
// 큐를 사용한 비동기 PDF 생성
dispatch(new GeneratePdfJob($analysisId));
```

### 4. 리소스 모니터링

```bash
# 메모리 사용량 모니터링
watch -n 1 free -h

# CPU 사용량 모니터링
htop

# 디스크 I/O 모니터링
iotop
```

## 📝 프로덕션 배포 체크리스트

- [ ] wkhtmltopdf 설치 완료
- [ ] 한글 폰트 설치 완료
- [ ] Laravel 패키지 설치 완료
- [ ] 환경변수 설정 완료
- [ ] 권한 설정 완료
- [ ] 기본 테스트 통과
- [ ] 브라우저 테스트 통과
- [ ] 메모리 제한 설정
- [ ] 로그 모니터링 설정
- [ ] 백업 전략 수립
- [ ] 테스트 라우트 제거 (보안)

## 🔗 추가 자료

- [wkhtmltopdf 공식 문서](https://wkhtmltopdf.org/)
- [knp-snappy GitHub](https://github.com/KnpLabs/snappy)
- [Noto CJK 폰트](https://fonts.google.com/noto/fonts)
- [Laravel PDF 생성 가이드](https://laravel.com/docs/11.x/filesystem)

## 🆘 지원

문제가 발생하면 다음 정보와 함께 이슈를 보고해주세요:

1. 운영체제 및 버전
2. PHP 버전
3. Laravel 버전
4. 오류 메시지 및 로그
5. 실행한 명령어들

---

📧 **연락처**: 기술 지원이 필요하면 개발팀에 문의하세요.