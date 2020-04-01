В данной статье мы рассмотрим возможности FFI появившегося в PHP версии 7.4, сравним возможности работать PHP с такими языками как Go, Rust, C++ без создания плагинов, а напрямую, а так-же где возможно вам пригодится использование данной функции, а где не стоит по нашему мнению.

Итак, что такое FFI: (https://en.wikipedia.org/wiki/Foreign_function_interface)
FFI это возможность вызвать библиотечную функицю написанную на одном языке из другого языка. К примеру, как вы догадываетесь, вызвать из PHP функцию написанную на Rust/C++/Go. Это работает следующим образом (общий взгляд)





СУПЕР НОВЫЙ ФФИ НА ПХП 7.4 (СРОЧНО В НОМЕР)


апи для плюсов тут: https://www.imagemagick.org/Magick++/Image++.html

Код для PHP

<?php
$ffi = FFI::cdef(
    "void resizeByPath(const char *path, const char *dimensions, const char *save_path);
     void resizeByBlob(const char *data, int data_len, const char *dimensions, const char *save_path);
     int fib(int n);",
    "/PATH/TO/SO/lib.so"); <-- путь к shared object библиотеке

$start = microtime(true);
$body = file_get_contents('/home/valery/Downloads/9t12lnng69i41.png');

//$ffi->resizeByPath("/home/valery/Downloads/9t12lnng69i41.png", "1024x1024", "/home/valery/Downloads/bypath.png"); // ~0.75
//$ffi->resizeByBlob($body, strlen($body), "1024x1024", "/home/valery/Downloads/byblob.png"); // ~0.75

//for ($i=0; $i < 1000000; $i++) {   // ~0.35
//    $ffi->fib(12);
//}

echo microtime(true) - $start;

resizeByPath --> этот метод принимает (виндо что)
resizeByBlob --> тоже самое, только принимает саму картинку

ФИБОНАЧЧИ
<?php

function fib($n)
{
    if ($n === 1 || $n === 2) {
        return 1;
    } else {
        return fib($n - 1) + fib($n - 2);
    }
}

$time_start = microtime(true);
for ($i = 0; $i < 1000000; $i++) {
    $v = fib(12);
}

echo '[PHP] native execution time:' . (microtime(true) - $time_start) . PHP_EOL;
// [PHP] native execution time:8.5178179740906  <------------ В 25!!! раз медленнее
----------------------------------------
Можно так-же объявлять h файлы, однако php FFI в данный момент не умеет работать с препроцессором

----------------С++---------------------

#include <ImageMagick-7/Magick++.h>
#include <ImageMagick-7/Magick++/Exception.h>
#include <iostream>

int main(int argc, char **argv) {
    Magick::InitializeMagick(nullptr);

    int* a = new int();
    void* b = reinterpret_cast<void*>(a);
}

extern "C" void resizeByPath(const char *path, const char *dimensions, const char *save_path) {
    Magick::InitializeMagick(nullptr);
    Magick::Image image;

    try {
        image.read(path);
        image.adaptiveResize(dimensions);
        image.write(save_path);
    } catch (Magick::Exception &err) {
        std::cout << "EXCEPTION: " << err.what() << std::endl;
    }
}

extern "C" void resizeByBlob(const char *data, int data_len, const char *dimensions, const char *save_path) {
    Magick::InitializeMagick(nullptr);
    Magick::Blob blob(data, data_len);
    Magick::Image image(blob);

    try {
        image.adaptiveResize(dimensions);
        image.adaptiveResize(save_path);
    } catch (Magick::Exception &err) {
        std::cout << "EXCEPTION: " << err.what() << std::endl;
    }
}

extern "C" int fib(int n) {
    if ((n == 1) || (n == 2)) {
        return 1;
    }
    return fib(n - 1) + fib(n - 2);
}


Компилим
g++ -o lib_magic.so  main.cpp -O3 -std=c++2a -shared -fPIC `Magick++-config --cppflags --cxxflags --ldflags --libs`




Есть ограничения. Это должен быть реально Си (во входных и возврщаемых параметрах). Внутри exten метода, можно производить преобразования в C++ структуры данных.
Данный подход конечно не пропогандирует переписать все и вся, однако, можно провести эксперимент на очень горячих местах-молотилках.


