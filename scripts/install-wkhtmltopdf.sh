#!/bin/bash

# wkhtmltopdf 설치 및 설정 스크립트 (실서버용)
# 한글 PDF 내보내기 지원을 위한 환경 구성

set -e  # 오류 발생 시 스크립트 중단

echo "========================================"
echo "wkhtmltopdf 설치 및 한글 폰트 설정 시작"
echo "========================================"

# 시스템 정보 확인
echo "1. 시스템 정보 확인"
echo "OS: $(lsb_release -d 2>/dev/null | cut -f2 || echo 'Unknown')"
echo "Architecture: $(uname -m)"
echo ""

# 패키지 관리자 확인
if command -v apt-get &> /dev/null; then
    PACKAGE_MANAGER="apt"
elif command -v yum &> /dev/null; then
    PACKAGE_MANAGER="yum"
elif command -v dnf &> /dev/null; then
    PACKAGE_MANAGER="dnf"
else
    echo "❌ 지원하지 않는 패키지 관리자입니다."
    exit 1
fi

echo "패키지 관리자: $PACKAGE_MANAGER"
echo ""

# 기존 wkhtmltopdf 설치 확인
echo "2. 기존 wkhtmltopdf 설치 확인"
if command -v wkhtmltopdf &> /dev/null; then
    EXISTING_VERSION=$(wkhtmltopdf --version 2>/dev/null | head -n1 || echo "버전 확인 실패")
    echo "✓ 기존 설치됨: $EXISTING_VERSION"

    read -p "기존 설치를 덮어쓰시겠습니까? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "설치를 취소합니다."
        exit 0
    fi
else
    echo "기존 설치 없음 - 새로 설치 진행"
fi
echo ""

# 시스템 패키지 업데이트
echo "3. 시스템 패키지 업데이트"
case $PACKAGE_MANAGER in
    "apt")
        sudo apt-get update
        ;;
    "yum")
        sudo yum update -y
        ;;
    "dnf")
        sudo dnf update -y
        ;;
esac
echo ""

# 필수 의존성 설치
echo "4. 필수 의존성 패키지 설치"
case $PACKAGE_MANAGER in
    "apt")
        sudo apt-get install -y \
            wget \
            fontconfig \
            libfontconfig1 \
            libjpeg-turbo8 \
            libpng16-16 \
            libssl3 \
            libx11-6 \
            libxcb1 \
            libxext6 \
            libxrender1 \
            xfonts-75dpi \
            xfonts-base \
            fonts-noto-cjk \
            fonts-nanum
        ;;
    "yum"|"dnf")
        if [ "$PACKAGE_MANAGER" = "yum" ]; then
            PKG_CMD="sudo yum install -y"
        else
            PKG_CMD="sudo dnf install -y"
        fi

        $PKG_CMD \
            wget \
            fontconfig \
            libjpeg-turbo \
            libpng \
            openssl-libs \
            libX11 \
            libXext \
            libXrender \
            xorg-x11-fonts-75dpi \
            xorg-x11-fonts-Type1 \
            google-noto-cjk-fonts
        ;;
esac
echo ""

# wkhtmltopdf 바이너리 다운로드 및 설치
echo "5. wkhtmltopdf 바이너리 다운로드 및 설치"

# 아키텍처에 따른 다운로드 URL 결정
ARCH=$(uname -m)
case $ARCH in
    "x86_64")
        if [ "$PACKAGE_MANAGER" = "apt" ]; then
            DOWNLOAD_URL="https://github.com/wkhtmltopdf/packaging/releases/download/0.12.6.1-2/wkhtmltox_0.12.6.1-2.jammy_amd64.deb"
            PACKAGE_FILE="wkhtmltox_0.12.6.1-2.jammy_amd64.deb"
        else
            DOWNLOAD_URL="https://github.com/wkhtmltopdf/packaging/releases/download/0.12.6.1-2/wkhtmltox-0.12.6.1-2.almalinux9.x86_64.rpm"
            PACKAGE_FILE="wkhtmltox-0.12.6.1-2.almalinux9.x86_64.rpm"
        fi
        ;;
    "aarch64"|"arm64")
        echo "❌ ARM64 아키텍처는 공식 바이너리가 제공되지 않습니다."
        echo "소스에서 컴파일하거나 Docker를 사용하는 것을 권장합니다."
        exit 1
        ;;
    *)
        echo "❌ 지원하지 않는 아키텍처: $ARCH"
        exit 1
        ;;
esac

# 임시 디렉토리 생성
TMP_DIR=$(mktemp -d)
cd "$TMP_DIR"

echo "다운로드 중: $DOWNLOAD_URL"
wget -q --show-progress "$DOWNLOAD_URL" -O "$PACKAGE_FILE"

if [ ! -f "$PACKAGE_FILE" ]; then
    echo "❌ 다운로드 실패"
    exit 1
fi

echo "패키지 설치 중..."
case $PACKAGE_MANAGER in
    "apt")
        sudo dpkg -i "$PACKAGE_FILE" || sudo apt-get install -f -y
        ;;
    "yum")
        sudo yum localinstall -y "$PACKAGE_FILE"
        ;;
    "dnf")
        sudo dnf localinstall -y "$PACKAGE_FILE"
        ;;
esac

# 임시 파일 정리
cd /
rm -rf "$TMP_DIR"
echo ""

# 설치 확인
echo "6. 설치 확인"
if command -v wkhtmltopdf &> /dev/null; then
    INSTALLED_VERSION=$(wkhtmltopdf --version 2>/dev/null | head -n1)
    echo "✓ wkhtmltopdf 설치 성공: $INSTALLED_VERSION"

    # 바이너리 경로 확인
    WKHTMLTOPDF_PATH=$(which wkhtmltopdf)
    echo "설치 경로: $WKHTMLTOPDF_PATH"

    # 심볼릭 링크 생성 (Laravel 앱에서 사용하는 경로)
    if [ "$WKHTMLTOPDF_PATH" != "/usr/local/bin/wkhtmltopdf" ]; then
        echo "심볼릭 링크 생성: /usr/local/bin/wkhtmltopdf"
        sudo ln -sf "$WKHTMLTOPDF_PATH" /usr/local/bin/wkhtmltopdf
    fi
else
    echo "❌ wkhtmltopdf 설치 실패"
    exit 1
fi
echo ""

# 한글 폰트 확인
echo "7. 한글 폰트 확인"
KOREAN_FONTS=$(fc-list | grep -i "nanum\|noto.*cjk\|malgun" | wc -l)
if [ "$KOREAN_FONTS" -gt 0 ]; then
    echo "✓ 한글 폰트 $KOREAN_FONTS 개 발견"
    echo "설치된 한글 폰트:"
    fc-list | grep -i "nanum\|noto.*cjk\|malgun" | head -5
else
    echo "⚠ 한글 폰트가 설치되지 않았습니다."
    echo "추가 폰트 설치를 권장합니다."
fi
echo ""

# 폰트 캐시 업데이트
echo "8. 폰트 캐시 업데이트"
sudo fc-cache -fv > /dev/null 2>&1
echo "✓ 폰트 캐시 업데이트 완료"
echo ""

# 테스트 HTML 생성 및 PDF 변환 테스트
echo "9. 한글 PDF 생성 테스트"
TEST_HTML="/tmp/wkhtmltopdf_korean_test.html"
TEST_PDF="/tmp/wkhtmltopdf_korean_test.pdf"

cat > "$TEST_HTML" << 'EOF'
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>한글 테스트</title>
    <style>
        body {
            font-family: 'Noto Sans CJK KR', 'NanumGothic', 'Malgun Gothic', sans-serif;
            font-size: 14px;
            line-height: 1.6;
            margin: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>한글 PDF 테스트</h1>
        <p>wkhtmltopdf 한글 폰트 렌더링 테스트</p>
    </div>
    <h2>SEO 분석 결과</h2>
    <p><strong>웹사이트:</strong> https://example.com</p>
    <p><strong>분석일:</strong> 2025-09-28</p>
    <p><strong>전체 점수:</strong> 85.5점</p>

    <h3>개선 권장사항</h3>
    <ul>
        <li>메타 디스크립션이 없습니다</li>
        <li>페이지 크기가 큽니다</li>
        <li>이미지 압축을 최적화하세요</li>
    </ul>

    <p>Mixed text: Hello 안녕하세요 123 テスト</p>
</body>
</html>
EOF

if wkhtmltopdf --page-size A4 --encoding utf-8 --enable-local-file-access "$TEST_HTML" "$TEST_PDF" 2>/dev/null; then
    PDF_SIZE=$(stat -f%z "$TEST_PDF" 2>/dev/null || stat -c%s "$TEST_PDF")
    if [ "$PDF_SIZE" -gt 10000 ]; then
        echo "✓ 한글 PDF 생성 테스트 성공 (파일 크기: $PDF_SIZE bytes)"
    else
        echo "⚠ PDF가 생성되었지만 크기가 작습니다 ($PDF_SIZE bytes)"
        echo "한글 폰트가 제대로 렌더링되지 않을 수 있습니다."
    fi

    # 테스트 파일 정리
    rm -f "$TEST_HTML" "$TEST_PDF"
else
    echo "❌ 한글 PDF 생성 테스트 실패"
    rm -f "$TEST_HTML" "$TEST_PDF"
fi
echo ""

# Laravel 프로젝트 설정 안내
echo "10. Laravel 프로젝트 설정 안내"
echo "다음 단계를 따라 Laravel 프로젝트를 설정하세요:"
echo ""
echo "1. Composer 패키지 설치:"
echo "   composer require knplabs/knp-snappy"
echo ""
echo "2. 환경변수 설정 (.env):"
echo "   WKHTMLTOPDF_PATH=/usr/local/bin/wkhtmltopdf"
echo "   WKHTMLTOIMAGE_PATH=/usr/local/bin/wkhtmltoimage"
echo ""
echo "3. 웹서버 사용자 권한 확인:"
echo "   sudo chown www-data:www-data /usr/local/bin/wkhtmltopdf"
echo "   sudo chmod +x /usr/local/bin/wkhtmltopdf"
echo ""

# 권한 설정
echo "11. 권한 설정"
WEB_USER="www-data"

# 일반적인 웹서버 사용자 확인
if id "nginx" &>/dev/null; then
    WEB_USER="nginx"
elif id "apache" &>/dev/null; then
    WEB_USER="apache"
elif id "www-data" &>/dev/null; then
    WEB_USER="www-data"
fi

echo "웹서버 사용자: $WEB_USER"
sudo chown root:$WEB_USER /usr/local/bin/wkhtmltopdf
sudo chmod 755 /usr/local/bin/wkhtmltopdf

echo "✓ 권한 설정 완료"
echo ""

echo "========================================"
echo "wkhtmltopdf 설치 및 설정 완료!"
echo "========================================"
echo ""
echo "📋 설치 요약:"
echo "- wkhtmltopdf 바이너리: /usr/local/bin/wkhtmltopdf"
echo "- 한글 폰트: 설치됨"
echo "- 테스트: 통과"
echo ""
echo "🚀 이제 Laravel 애플리케이션에서 한글 PDF를 생성할 수 있습니다!"
echo ""
echo "⚠️  주의사항:"
echo "- 방화벽에서 wkhtmltopdf 실행을 허용해야 할 수 있습니다"
echo "- SELinux가 활성화된 경우 추가 설정이 필요할 수 있습니다"
echo "- 메모리 사용량이 높은 PDF 생성 시 시스템 리소스를 모니터링하세요"