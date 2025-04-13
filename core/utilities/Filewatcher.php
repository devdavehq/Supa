<?php
// FileWatcher.php
class FileWatcher {
    public $filesToWatch = [];
    private $sessionKey = 'file_watcher_timestamps';
    private $useSSE = true;
    private $lastCheckTime = 0;

    public function __construct(array $filesToWatch = [], bool $useSSE = true) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->filesToWatch = $this->expandFilePatterns($filesToWatch);
        $this->useSSE = $useSSE;
        $this->initializeTimestamps();
        $this->lastCheckTime = time();
    }

    private function expandFilePatterns(array $patterns) {
        $expanded = [];
        foreach ($patterns as $pattern) {
            $matches = glob($pattern, GLOB_BRACE);
            if ($matches) {
                $expanded = array_merge($expanded, $matches);
            }
        }
        return array_unique($expanded);
    }

    private function initializeTimestamps() {
        if (!isset($_SESSION[$this->sessionKey])) {
            $_SESSION[$this->sessionKey] = [];
        }

        foreach ($this->filesToWatch as $file) {
            if (file_exists($file)) {
                $_SESSION[$this->sessionKey][$file] = filemtime($file);
            }
        }
    }

    public function watch() {
        if ($this->useSSE && $this->isSSEConnection()) {
            $this->handleSSEConnection();
        } elseif ($this->useSSE) {
            $this->injectSSEClient();
        } else {
            $this->phpWatch();
        }
    }

    private function isSSEConnection() {
        return isset($_SERVER['HTTP_ACCEPT']) && 
               strpos($_SERVER['HTTP_ACCEPT'], 'text/event-stream') !== false;
    }

    private function handleSSEConnection() {

        if (headers_sent()) return;
        
        set_time_limit(0);
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        
        ob_implicit_flush(true);
        ob_end_flush();
        
        while (true) {
            $changedFiles = $this->checkForChanges();
            
            if (!empty($changedFiles)) {
                echo "event: filechange\n";
                echo "data: " . json_encode([
                    'changed' => true,
                    'files' => $changedFiles,
                    'time' => date('Y-m-d H:i:s')
                ]) . "\n\n";
                break;
            }
            
            usleep(500000);
            echo ": heartbeat\n\n";
        }
        
        exit;
    }

    private function checkForChanges() {
        $changedFiles = [];
        $currentTime = time();
        
        if (($currentTime - $this->lastCheckTime) < 0.5) {
            return $changedFiles;
        }
        
        $this->lastCheckTime = $currentTime;
        
        foreach ($this->filesToWatch as $file) {
            if (!file_exists($file)) continue;
            
            $currentMtime = filemtime($file);
            $lastMtime = $_SESSION[$this->sessionKey][$file] ?? 0;
            
            if ($currentMtime > $lastMtime) {
                $_SESSION[$this->sessionKey][$file] = $currentMtime;
                $changedFiles[] = $file;
            }
        }
        
        return $changedFiles;
    }

    private function injectSSEClient() {
        $watchUrl = htmlspecialchars($_SERVER['REQUEST_URI']);
        
        echo <<<HTML
        <script>
        (function() {
            const eventSource = new EventSource("{$watchUrl}?file_watcher_sse=1");
            
            eventSource.addEventListener('filechange', function(e) {
                const data = JSON.parse(e.data);
                console.log('[FileWatcher] Changes detected in:', data.files);
                window.location.reload();
            });
            
            eventSource.onerror = function() {
                console.log('[FileWatcher] SSE connection error. Falling back to polling...');
                eventSource.close();
                setTimeout(() => window.location.reload(), 2000);
            };
        })();
        </script>
HTML;
    }

    public function handleRequest() {
        if (isset($_GET['file_watcher_sse'])) {
            $this->useSSE = true;
            $this->watch();
        } elseif (isset($_GET['file_watcher_check'])) {
            $this->handleAjaxCheck();
        }
    }

    // The missing handleAjaxCheck method
    public function handleAjaxCheck() {
        $changedFiles = $this->checkForChanges();
        
        header('Content-Type: application/json');
        echo json_encode([
            'reload' => !empty($changedFiles),
            'changed_files' => $changedFiles,
            'timestamp' => time()
        ]);
        exit;
    }

    private function phpWatch() {
        $changedFiles = $this->checkForChanges();
        if (!empty($changedFiles)) {
            $this->reloadPage();
        }
    }

    private function reloadPage() {
        header("Refresh:0");
        exit;
    }

    public static function createAndWatch(array $files, bool $useSSE = true) {
        $watcher = new self($files, $useSSE);
        $watcher->handleRequest();
        $watcher->watch();
    }
}