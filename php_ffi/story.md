In this article, we’re going to consider the capabilities of FFI that was introduced in PHP version 7.4, plus we’re going to compare the abilities of PHP to work with such languages as `Go`, `Rust`, `C++` without creating plug-ins, but directly. Moreover, we’re going to cover the topic where it is possible to use this function, and where, in our opinion, it’s not worth doing it.
So, what is FFI: 
[FFI](https://en.wikipedia.org/wiki/Foreign_function_interface)
FFI is the ability to call a library function written in one language from another one. For example, as you might guess, it’s possible to call a function written in Rust/C++/Go from PHP. In order to connect an interpreted language with a compiled language, the libffilibrary is used: [Repo](https://en.wikipedia.org/wiki/Libffi). 
Since the interpreted languages do not know where specifically (in which registers) to search for the parameters of the called function, as well as where to get the results of the function after the call. All this work for interpreted languages ​​is done by Libffi. So, you need to install this library, as  it is part of the system libraries (Linux).
All the experiments will be conducted on ArchLinux (5.6.1 kernel), Libffi 3.2.1.
What's the use of it? It is certainly interesting to explore new language features, but is there any practical sense in this? I’m going to try to prove this in the course of the article.
So, PHP.
[link](https://www.php.net/manual/en/intro.ffi.php)
The title itself immediately describes that at the time of writing, this is an experimental feature of the PHP language.
For our example, we are taking such an interesting problem as calculating the Fibonacci sequences. And of course, not in the most efficient way, — with the help of recursion. This is done in order to use the processor as much as possible, as well as to prevent compiled languages from optimizing this function (for example, applying the technique of unwinding cycle (https://en.wikipedia.org/wiki/Loop_unrolling )


Let's get started.
For PHP the first thing we should do is to uncomment the extension ffi in php.ini (/etc/php/php.ini in ArchLinux).
Next we need to declare our conditional interface. There are some restrictions that are currently present in PHP FFI, in particular the inability to use a C-preprocessor (#include, #define, etc., except for some special ones)
```php
$ffi = FFI::cdef(
     "int Fib(int n);",
    "/PATH/TO/SO/lib.so");
```
1. `FFI :: cdef` - with this operation we define the interaction interface.
2. `int Fib (int n)` - IT’s the name of the exported method of the compiled language. We will talk how to do it right a little bit later.
3. `/ PATH / TO / SO / lib.so` - the path to the dynamic library where the function above is located.

The full php script we’re using:
```php
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
```
The first step is to make a dynamic library in the Rust language 
([link](https://www.rust-lang.org/))
This will require some preparation:
1. On any platform, for the installation we need only one instruction from here - [link](https://rustup.rs)
2. After that, create a project anywhere with the command `cargo new rust_php_ffi`. And that’s it!

Here is our function:
```rust
//src/lib.rs

#[no_mangle]
extern "C" fn Fib(n: i32) -> i32 {
    if (n == 0) || (n == 1) {
        return 1;
    }

    Fib(n - 1) + Fib(n - 2)
}
```

It is very important not to forget to add the attribute # [no_mangle] to the required function, because otherwise the compiler will replace the name of your function with something like: `_аgs @ fs34`. And exporting it to PHP, libffi simply won’t be able to find a function named Fib in the dynamic library. You can read more here: [link](https://en.wikipedia.org/wiki/Name_mangling).

In Cargo.toml you need to add the attribute:
```rust
[lib]
crate-type = ["cdylib"]
```
I would like to draw your attention to the fact that there are three options for a dynamic library through an attribute in Cargo.toml.
1. `dylib` - Rust shared library with unstable ABI, which can change from version to version (as in `Go` internal ABI)
2. `cdylib` is a dynamic library for useing in C / C ++. This is our choice.
3. `rlib` - Rust static library with rlib extestion (.rlib). It also contains metadata used to link various rlibs written respectively in Rust

Let’s compile: `cargo build --release`. And in the folder `target / release` we see the` .so` file. This will be our dynamic library.
C++

Next in line is C ++.
Here everything is quite simple, too:

```cpp
// in php_cpp_ffi.cpp

int main() {
    
}

extern "C" int Fib(int n) {
    if ((n==1) || (n==2)) {
        return 1;
    }

    return Fib(n-1) + Fib(n-2);
}
```
We need to declare the `extern` function so that it can be imported from php. 

Let’s compile:
`g++ -fPIC -O3 -shared src / php_cpp_ffi.cpp -o ../ lib / libphp_cpp_ffi.so`. 
A few comments on the compilation:
  1. `-fPIC` position-independence code. For a dynamic library, it is important to be independent of the address at which it is loaded in memory.
  2. `-O3` - maximum optimization

And next in line is `Golang`.

That is a language with runtime. A special mechanism for interacting with dynamic libraries was developed for Go, which is called - `CGO` 
[link](https://golang.org/cmd/cgo/)
This comment explains well how this mechanism works: 
[link](https://github.com/golang/go/blob/860c9c0b8df6c0a2849fdd274a0a9f142cba3ea5/src/cmd/cgo/doc.go#L378-L471)
Also, since CGO interprets the generated errors from C, there is no way to use optimizations, as we did in C++ [link](https://go-review.googlesource.com/c/go/+/23231) and [link](https://go-review.googlesource.com/c/go/+/23231/2/src/cmd/cgo/gcc.go)

So, welcome the code:

```go
package main

import (
        "C"
)

// we need to have empty main in package main :)
// because -buildmode=c-shared requires exactly one main package
func main() {

}

//export Fib
func Fib(n C.int) C.int {
        if n == 1 || n == 2 {
                return 1
        }

        return Fib(n-1) + Fib(n-2)
}
```
So, all this is the same Fib function, however, for this function to be exported in a dynamic library, we need to add the comment above (a sort of GO attribute) `// export Fib`.
Let’s compile: `go build -o ../lib/libphp_go_ffi.so -buildmode = c-shared`. I’ll also pay attention that we need to add `-buildmode = c-shared` to get a dynamic library.
 We get 2 files at the output. A file with the headers `.h` and` .so` is a dynamic library. We do not need the file with headers, since we know the name of the function, and FFI php is rather limited in working with the C preprocessor.

Rocket launch:
After we’ve written everything (source codes are provided), we can make a small Makefile to collect all this (it is also located in the repository). After we call `make build` in the` lib` folder, 4 files will appear. Two for GO (.h / .so) and one for Rust and C ++.

Makefile:
```makefile
build_cpp:
        echo 'Building cpp...'
        cd cpp && g++ -fPIC -O3 -shared src/php_cpp_ffi.cpp -o libphp_cpp_ffi.so

build_go:
        echo 'Building golang...'
        cd golang && go build -o libphp_go_ffi.so -buildmode=c-shared

build_rust:
        echo 'Building Rust...'
        cargo build --release && mv rust/target/release/libphp_ffi.so libphp_rust_ffi.so

build: build_cpp build_go build_rust


run:
        php php/php_fib.php
```
Then we can go to the `php` folder and run our script (or via the Makefile -` make run`). I also want to pay attention to the fact that in the php script in `FFI :: cdef` the paths to the` .so` files are hardcoded, so for everything to work, please run through `make run`. The result of the work is as follows:
1. [PHP] execution time: 8.6763260364532 Result: 144
2. [RUST] execution time: 0.32162690162659 Result: 144
3. [CPP] execution time: 0.3515248298645 Result: 144
4. [GOLANG] execution time: 5.0730509757996 Result: 144

As expected, PHP showed the lowest result in the CPU loaded with calculations, but on the whole, it’s pretty fast for a million calls.
A surprise might seem to be the running time of CGO, a little less than PHP. In essence, this happens due to `calling-conventions` because of unstable ABI. CGO is forced to carry out type conversion operations from Go-types to C (you can see in the ‘h’ file that is obtained after building the GO dynamic library) types, as well as the fact that you have to copy the incoming and return values ​​for C and GO compatibility 
[link](https://en.wikipedia.org/wiki/X86_calling_conventions ).
Rust and C ++ showed the best results as we had expected, since they have a stable ABI and the only layer between php and these languages ​​is libffi.

Conclusion:

Of course, it’s unlikely that this approach is currently ready for bloody production, as it can carry a lot of pitfalls. This is what php developers tell us about:
```
Warning

This extension is EXPERIMENTAL. The behavior of this extension including the names of its functions and any other documentation surrounding this extension may change without notice in a future release of PHP. This extension should be used at your own risk.
```
There are no normal ways to work with the preprocessor.
This article just shows the features of a new language feature. However, if this feature of PHP becomes stable, imagine how it would be possible to optimize hot spots in your code?
