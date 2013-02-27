<?php

require_once("xenon/xenon.php");
xenon\frameworks\cherryphp::bootstrap(__DIR__);

use Cherry\Expm\Process\CoRoutine;

// Using a class as a coroutine is more awesome!
class PrimesCoroutine extends CoRoutine {
    public function main($start,$step,$max) {
        $n = 0;
        $ts = microtime(true);
        for($cur = $start; $cur < $max; $cur+=$step) {
            $n++;
            if ($this->testPrime($cur)) {
                $this->sendMessage([ 'done'=>false, 'found'=>$n ]);
            }
            usleep(5000);
        }
        $te = microtime(true) - $ts;
        $this->sendMessage([ 'done'=>true, 'rate'=>($n/$te) ]);
    }
    public function testPrime($n) {
        $mx = sqrt($n);
        for($m = 2; $m <= $mx; $m++) {
            $r = ($n / $m);
            if ($r == (int)$r) return false;
        }
        return true;
    }
}

$cr = 10;
$ca = [];
$tc = [];
$tl = [];
$max = 10000;
for($n = 0; $n < $cr; $n++) {
    $tc[] = 0;
    $tl[] = 0;
    $co = new PrimesCoroutine();
    $co->start($n+1,$cr,$max);
    $ca[] = $co;
}
$done = 0;
$primes = [];
$laststatus = 0;
while($done < $cr) {
    foreach($ca as $idx=>$co) {
        while (($msg = $co->pollMessage())) {
            if ($msg['done']) {
                printf("Thread completed: %.2f primes/sec\n", $msg['rate']);
                $done++;
                echo ".";
            } else {
                $tc[$idx]++;
                $primes[] = $msg['found'];
                $tl[$idx] = $msg['found'];
            }
        }
        if (!$co->isRunning()) {
            echo "Dead thread detected\n";
            $done++;
            $ca[$idx] = null;
        }
    }
    if (microtime(true)>$laststatus+.5) {
        $laststatus = microtime(true);
        echo "\r\033[K".join(" | ",$tl);
    }
    usleep(10000);
}
echo "\n\n";
sort($primes);
$primes = array_chunk($primes,10);
foreach($primes as $primeset) {
    foreach($primeset as $prime) printf("%6d  ",$prime);
    printf("\n");
}
echo "\nCalculated:\n";
foreach($tc as $idx=>$num) {
    echo "  Thread {$idx}: {$num} results\n";
}
