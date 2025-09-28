#!/bin/bash

# Laravel PDF 설정 스크립트
# wkhtmltopdf 설치 후 Laravel 프로젝트 설정 자동화

set -e

echo "========================================="
echo "Laravel PDF 설정 자동화 스크립트"
echo "========================================="

# 현재 디렉토리 확인
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

echo "프로젝트 루트: $PROJECT_ROOT"
echo ""

# Laravel 프로젝트 확인
if [ ! -f "$PROJECT_ROOT/artisan" ]; then
    echo "❌ Laravel 프로젝트를 찾을 수 없습니다."
    echo "이 스크립트는 Laravel 프로젝트 루트의 scripts/ 디렉토리에서 실행해야 합니다."
    exit 1
fi

echo "✓ Laravel 프로젝트 확인됨"
echo ""

# wkhtmltopdf 설치 확인
echo "1. wkhtmltopdf 설치 확인"
if ! command -v wkhtmltopdf &> /dev/null; then
    echo "❌ wkhtmltopdf가 설치되지 않았습니다."
    echo "먼저 install-wkhtmltopdf.sh 스크립트를 실행하세요."
    exit 1
fi

WKHTMLTOPDF_VERSION=$(wkhtmltopdf --version 2>/dev/null | head -n1)
echo "✓ $WKHTMLTOPDF_VERSION"
echo ""

# Composer 패키지 확인 및 설치
echo "2. Composer 패키지 확인"
cd "$PROJECT_ROOT"

if ! grep -q "knplabs/knp-snappy" composer.json; then
    echo "knp-snappy 패키지 설치 중..."
    composer require knplabs/knp-snappy
    echo "✓ knp-snappy 패키지 설치 완료"
else
    echo "✓ knp-snappy 패키지 이미 설치됨"
fi
echo ""

# 환경변수 설정
echo "3. 환경변수 설정"
ENV_FILE="$PROJECT_ROOT/.env"

if [ ! -f "$ENV_FILE" ]; then
    echo "❌ .env 파일을 찾을 수 없습니다."
    exit 1
fi

# wkhtmltopdf 경로 확인
WKHTMLTOPDF_PATH=$(which wkhtmltopdf)
WKHTMLTOIMAGE_PATH=$(which wkhtmltoimage 2>/dev/null || echo "")

# .env 파일에 설정 추가/업데이트
update_env_var() {
    local key="$1"
    local value="$2"
    local file="$3"

    if grep -q "^${key}=" "$file"; then
        # 기존 설정 업데이트
        if [[ "$OSTYPE" == "darwin"* ]]; then
            # macOS
            sed -i '' "s|^${key}=.*|${key}=${value}|" "$file"
        else
            # Linux
            sed -i "s|^${key}=.*|${key}=${value}|" "$file"
        fi
        echo "  ✓ $key 업데이트됨"
    else
        # 새 설정 추가
        echo "" >> "$file"
        echo "# PDF Generation Settings" >> "$file"
        echo "${key}=${value}" >> "$file"
        echo "  ✓ $key 추가됨"
    fi
}

echo "환경변수 설정 중..."
update_env_var "WKHTMLTOPDF_PATH" "$WKHTMLTOPDF_PATH" "$ENV_FILE"
if [ -n "$WKHTMLTOIMAGE_PATH" ]; then
    update_env_var "WKHTMLTOIMAGE_PATH" "$WKHTMLTOIMAGE_PATH" "$ENV_FILE"
fi

echo "✓ 환경변수 설정 완료"
echo ""

# 권한 설정 확인
echo "4. 권한 설정 확인"

# 웹서버 사용자 확인
WEB_USER="www-data"
if id "nginx" &>/dev/null; then
    WEB_USER="nginx"
elif id "apache" &>/dev/null; then
    WEB_USER="apache"
elif id "www-data" &>/dev/null; then
    WEB_USER="www-data"
fi

echo "웹서버 사용자: $WEB_USER"

# 임시 디렉토리 권한 설정
TEMP_DIRS=("/tmp" "/var/tmp")
for dir in "${TEMP_DIRS[@]}"; do
    if [ -d "$dir" ]; then
        sudo chmod 1777 "$dir" 2>/dev/null || echo "  ⚠ $dir 권한 설정 실패 (무시 가능)"
    fi
done

# Laravel storage 디렉토리 권한 설정
STORAGE_DIR="$PROJECT_ROOT/storage"
if [ -d "$STORAGE_DIR" ]; then
    sudo chown -R $WEB_USER:$WEB_USER "$STORAGE_DIR" 2>/dev/null || echo "  ⚠ storage 디렉토리 권한 설정 실패"
    sudo chmod -R 755 "$STORAGE_DIR" 2>/dev/null || echo "  ⚠ storage 디렉토리 권한 설정 실패"
    echo "✓ storage 디렉토리 권한 설정 완료"
fi

echo ""

# Laravel 설정 파일 생성
echo "5. Laravel 설정 파일 생성"

CONFIG_DIR="$PROJECT_ROOT/config"
SNAPPY_CONFIG="$CONFIG_DIR/snappy.php"

if [ ! -f "$SNAPPY_CONFIG" ]; then
    cat > "$SNAPPY_CONFIG" << 'EOF'
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Snappy PDF / Image Configuration
    |--------------------------------------------------------------------------
    |
    | This option contains settings for PDF generation.
    |
    | Enabled:
    |
    |    Whether to load PDF / Image generation.
    |
    | Binary:
    |
    |    The file path of the wkhtmltopdf / wkhtmltoimage executable.
    |
    | Timeout:
    |
    |    The amount of time to wait (in seconds) before PDF / Image generation is stopped.
    |    Setting this to false disables the timeout (unlimited processing time).
    |
    | Options:
    |
    |    The wkhtmltopdf command options. These are passed directly to wkhtmltopdf.
    |    See https://wkhtmltopdf.org/usage/wkhtmltopdf.txt for all options.
    |
    | Env:
    |
    |    The environment variables to pass to the process.
    |
    */

    'pdf' => [
        'enabled' => true,
        'binary'  => env('WKHTMLTOPDF_PATH', '/usr/local/bin/wkhtmltopdf'),
        'timeout' => false,
        'options' => [
            'page-size' => 'A4',
            'orientation' => 'portrait',
            'encoding' => 'utf-8',
            'enable-local-file-access' => true,
            'margin-top' => '10mm',
            'margin-bottom' => '10mm',
            'margin-left' => '10mm',
            'margin-right' => '10mm',
            'disable-smart-shrinking' => true,
        ],
        'env' => [],
    ],

    'image' => [
        'enabled' => true,
        'binary'  => env('WKHTMLTOIMAGE_PATH', '/usr/local/bin/wkhtmltoimage'),
        'timeout' => false,
        'options' => [
            'format' => 'jpg',
            'width' => 1024,
            'height' => 768,
            'encoding' => 'utf-8',
            'enable-local-file-access' => true,
        ],
        'env' => [],
    ],

];
EOF

    echo "✓ snappy.php 설정 파일 생성됨"
else
    echo "✓ snappy.php 설정 파일 이미 존재함"
fi
echo ""

# 테스트 컨트롤러 생성
echo "6. 테스트 컨트롤러 생성"

TEST_CONTROLLER="$PROJECT_ROOT/app/Http/Controllers/PdfTestController.php"

if [ ! -f "$TEST_CONTROLLER" ]; then
    cat > "$TEST_CONTROLLER" << 'EOF'
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Knp\Snappy\Pdf;

class PdfTestController extends Controller
{
    public function testKoreanPdf()
    {
        try {
            $pdf = new Pdf(config('snappy.pdf.binary'));
            $pdf->setOptions(config('snappy.pdf.options'));

            $html = view('test.korean-pdf-test')->render();
            $pdfContent = $pdf->getOutputFromHtml($html);

            $filename = 'korean-pdf-test-' . date('Y-m-d-H-i-s') . '.pdf';

            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Content-Length' => strlen($pdfContent)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'PDF 생성 실패',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
EOF

    echo "✓ PdfTestController 생성됨"
else
    echo "✓ PdfTestController 이미 존재함"
fi

# 테스트 뷰 생성
TEST_VIEW_DIR="$PROJECT_ROOT/resources/views/test"
TEST_VIEW="$TEST_VIEW_DIR/korean-pdf-test.blade.php"

if [ ! -d "$TEST_VIEW_DIR" ]; then
    mkdir -p "$TEST_VIEW_DIR"
fi

if [ ! -f "$TEST_VIEW" ]; then
    cat > "$TEST_VIEW" << 'EOF'
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>한글 PDF 테스트</title>
    <style>
        body {
            font-family: 'Noto Sans CJK KR', 'NanumGothic', 'Malgun Gothic', 'Arial Unicode MS', sans-serif;
            font-size: 14px;
            line-height: 1.6;
            margin: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #4f46e5;
            margin: 0;
            font-size: 24px;
        }
        .test-section {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: #f9f9f9;
        }
        .success {
            color: #065f46;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>한글 PDF 생성 테스트</h1>
        <p>wkhtmltopdf + Laravel 한글 폰트 렌더링 확인</p>
    </div>

    <div class="test-section">
        <h2>기본 한글 텍스트</h2>
        <p><strong>제목:</strong> SEO 분석 결과 리포트</p>
        <p><strong>설명:</strong> 웹사이트의 검색엔진 최적화 상태를 분석한 결과입니다.</p>
        <p><strong>생성일:</strong> {{ now()->format('Y년 m월 d일 H시 i분') }}</p>
    </div>

    <div class="test-section">
        <h2>다양한 한글 텍스트</h2>
        <ul>
            <li>메타 디스크립션이 없습니다</li>
            <li>페이지 크기가 큽니다</li>
            <li>이미지 압축을 최적화하세요</li>
            <li>H1 태그가 설명적이지 않습니다</li>
            <li>내부 링크에 텍스트가 부족합니다</li>
        </ul>
    </div>

    <div class="test-section">
        <h2>Mixed Language Test</h2>
        <p>English: This is a test of mixed languages.</p>
        <p>한글: 이것은 혼합 언어 테스트입니다.</p>
        <p>Mixed: Hello 안녕하세요 123 テスト العربية</p>
        <p>숫자: 1234567890</p>
        <p>특수문자: !@#$%^&*()</p>
    </div>

    <div class="test-section success">
        <h2>✅ 테스트 성공!</h2>
        <p>이 텍스트가 올바르게 표시된다면 한글 PDF 생성이 정상적으로 작동하고 있습니다.</p>
    </div>

    <div style="margin-top: 40px; text-align: center; font-size: 12px; color: #666;">
        생성 시간: {{ now()->format('Y-m-d H:i:s') }} | Laravel + wkhtmltopdf 한글 테스트
    </div>
</body>
</html>
EOF

    echo "✓ 테스트 뷰 생성됨"
else
    echo "✓ 테스트 뷰 이미 존재함"
fi
echo ""

# 테스트 라우트 추가 안내
echo "7. 테스트 라우트 설정"
ROUTES_FILE="$PROJECT_ROOT/routes/web.php"

if ! grep -q "PdfTestController" "$ROUTES_FILE"; then
    echo ""
    echo "다음 라우트를 routes/web.php에 수동으로 추가하세요:"
    echo ""
    echo "use App\\Http\\Controllers\\PdfTestController;"
    echo ""
    echo "Route::get('/test/korean-pdf', [PdfTestController::class, 'testKoreanPdf'])"
    echo "    ->name('test.korean-pdf');"
    echo ""
    echo "⚠️  보안상 프로덕션 환경에서는 이 테스트 라우트를 제거하세요."
else
    echo "✓ 테스트 라우트 이미 설정됨"
fi
echo ""

# Laravel 캐시 클리어
echo "8. Laravel 캐시 클리어"
php artisan config:clear
php artisan cache:clear
php artisan view:clear
echo "✓ Laravel 캐시 클리어 완료"
echo ""

# 최종 테스트
echo "9. 최종 테스트"
echo "브라우저에서 다음 URL에 접속하여 한글 PDF 생성을 테스트하세요:"
echo ""
echo "http://your-domain.com/test/korean-pdf"
echo ""
echo "또는 artisan 명령어로 테스트:"
echo "php artisan tinker"
echo ""

echo "========================================="
echo "Laravel PDF 설정 완료!"
echo "========================================="
echo ""
echo "📋 설정 요약:"
echo "- Composer 패키지: knplabs/knp-snappy ✓"
echo "- 환경변수: WKHTMLTOPDF_PATH ✓"
echo "- 설정 파일: config/snappy.php ✓"
echo "- 테스트 컨트롤러: PdfTestController ✓"
echo "- 테스트 뷰: korean-pdf-test.blade.php ✓"
echo ""
echo "🚀 이제 Laravel에서 한글 PDF를 생성할 수 있습니다!"
echo ""
echo "📌 다음 단계:"
echo "1. 테스트 라우트 추가 (routes/web.php)"
echo "2. 브라우저에서 /test/korean-pdf 접속하여 테스트"
echo "3. 프로덕션 배포 시 테스트 코드 제거"