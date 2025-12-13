<?php
// app/Api/RateLimiter.php

namespace App\Api;

class RateLimiter {
    private int $maxRequests;
    private int $timeWindow; // Sekunden
    
    public function __construct(int $maxRequests = 100, int $timeWindow = 60) {
        $this->maxRequests = $maxRequests;
        $this->timeWindow = $timeWindow;
    }
    
    public function check(string $identifier): bool {
        $key = "rate_limit:$identifier";
        $current = $_SESSION[$key] ?? ['count' => 0, 'reset' => time() + $this->timeWindow];
        
        if (time() > $current['reset']) {
            $current = ['count' => 0, 'reset' => time() + $this->timeWindow];
        }
        
        $current['count']++;
        $_SESSION[$key] = $current;
        
        return $current['count'] <= $this->maxRequests;
    }
}