fn main() {

}

#[no_mangle]
extern "C" fn Fib(n: i32) -> i32 {
    if (n == 0) || (n == 1) {
        return 1;
    }

    Fib(n - 1) + Fib(n - 2)
}