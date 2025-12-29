# Queue 명령어 가이드

## Job 설정

### AnalyzeUrl Job

| 설정 | 값 | 설명 |
|------|-----|------|
| Queue | `seo_analysis` | 큐 이름 |
| Timeout | 300초 (5분) | 작업 최대 실행 시간 |
| Tries | 2 | 재시도 횟수 |
| Backoff | 15초 | 재시도 간격 |

## 기본 명령어

### 큐 워커 실행

```bash
# seo_analysis 큐 실행 (권장)
php artisan queue:work --queue=high,seo_analysis,seo_reporting,default

# 한 번만 실행
php artisan queue:work --queue=high,seo_analysis,seo_reporting,default --once

# 상세 로그 출력
php artisan queue:work --queue=high,seo_analysis,seo_reporting,default -v
php artisan queue:work --queue=high,seo_analysis,seo_reporting,default -vvv
```

### 옵션 설명

```bash
php artisan queue:work --queue=seo_analysis \
    --timeout=300 \    # Job 타임아웃 (초)
    --tries=2 \        # 재시도 횟수
    --sleep=3 \        # Job 없을 때 대기 시간 (초)
    --memory=128       # 메모리 제한 (MB)
```

## 상태 확인

### 대기 중인 Jobs

```bash
# Tinker로 확인
php artisan tinker
>>> DB::table('jobs')->count()
>>> DB::table('jobs')->get()
```

```sql
-- SQL로 확인
SELECT id, queue,
       FROM_UNIXTIME(available_at) AS available_time,
       FROM_UNIXTIME(created_at) AS created_time,
       attempts
FROM jobs;
```

### 실패한 Jobs

```bash
# 실패 목록 조회
php artisan queue:failed

# 특정 Job 상세 보기
php artisan queue:failed --id=5
```

## 문제 해결

### Job이 처리되지 않을 때

1. **큐 이름 확인** - 반드시 `--queue=seo_analysis` 지정
   ```bash
   php artisan queue:work --queue=seo_analysis --once -v
   ```

2. **reserved 상태 확인**
   ```sql
   SELECT id, reserved_at, attempts FROM jobs;
   ```

3. **워커 재시작**
   ```bash
   php artisan queue:restart
   ```

### 실패한 Jobs 처리

```bash
# 모든 실패 Job 재시도
php artisan queue:retry all

# 특정 Job 재시도
php artisan queue:retry 5

# 모든 실패 Job 삭제
php artisan queue:flush
```

### 강제 정리 (개발 환경)

```bash
php artisan tinker
>>> DB::table('jobs')->truncate()
>>> DB::table('failed_jobs')->truncate()
```

## 백그라운드 실행

### 백그라운드로 실행

```bash
# nohup으로 백그라운드 실행
nohup php artisan queue:work --queue=high,seo_analysis,seo_reporting,default --tries=2 --timeout=300 > storage/logs/queue.log 2>&1 &

# 프로세스 ID 확인
echo $!

# 또는 jobs 명령으로 확인
jobs -l
```

### 실행 중인 워커 확인

```bash
# 프로세스 확인
ps aux | grep "queue:work"

# 또는
pgrep -f "queue:work"
```

### 워커 종료

```bash
# 프로세스 ID로 종료 (graceful)
kill <PID>

# 모든 queue:work 프로세스 종료
pkill -f "queue:work"

# 강제 종료 (권장하지 않음)
kill -9 <PID>
```

### Laravel 명령으로 종료

```bash
# 현재 job 완료 후 종료 (graceful restart)
php artisan queue:restart
```

> `queue:restart`는 워커를 즉시 종료하지 않고, 현재 처리 중인 job이 완료되면 종료합니다.
> Supervisor 등에서 자동 재시작 설정 시 새 워커가 시작됩니다.

## 프로덕션 실행

### Supervisor 설정

`/etc/supervisor/conf.d/seovalidator-worker.conf`:

```ini
[program:seovalidator-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/seovalidator/artisan queue:work --queue=seo_analysis --sleep=3 --tries=2 --timeout=300
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/seovalidator/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
# Supervisor 적용
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start seovalidator-worker:*
```

### 코드 배포 후

```bash
# 워커 재시작 (graceful)
php artisan queue:restart
```

## 로그 확인

```bash
# Laravel 로그
tail -f storage/logs/laravel.log

# 특정 키워드 필터
tail -f storage/logs/laravel.log | grep -E "(AnalyzeUrl|queue)"
```
