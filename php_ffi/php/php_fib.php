<?php
// =========================== PHP NATIVE ===========================
function fib($n)
{
    if ($n === 1 || $n === 2) {
        return 1;
    }
    return fib($n - 1) + fib($n - 2);
}

$start = microtime(true);
$p = 0;
for ($i = 0; $i < 1000000; $i++) {
    $p = fib(12);
}

echo '[PHP] execution time: '.(microtime(true) - $start).' Result: '.$p.PHP_EOL;

// =========================== RUST FFI ===========================
$rust_ffi = FFI::cdef(
    "int Fib(int n);",
    "lib/libphp_rust_ffi.so");

$start = microtime(true);
$r = 0;
for ($i=0; $i < 1000000; $i++) { 
   $r = $rust_ffi->Fib(12);
}

echo '[RUST] execution time: '.(microtime(true) - $start).' Result: '.$r.PHP_EOL;

// =========================== CPP FFI ===========================
$cpp_ffi = FFI::cdef(
    "int Fib(int n);",
    "lib/libphp_cpp_ffi.so");

$start = microtime(true);
$c = 0;
for ($i=0; $i < 1000000; $i++) { 
   $c = $cpp_ffi->Fib(12);
}

echo '[CPP] execution time: '.(microtime(true) - $start).' Result: '.$c.PHP_EOL;

// =========================== GLANG FFI ===========================
$golang_ffi = FFI::cdef(
    "int Fib(int n);",
    "lib/libphp_go_ffi.so");

$start = microtime(true);

for ($i=0; $i < 1000000; $i++) { 
   $golang_ffi->Fib(12);
}

echo '[GOLANG] execution time: '.(microtime(true) - $start).' Result: '.$c.PHP_EOL;