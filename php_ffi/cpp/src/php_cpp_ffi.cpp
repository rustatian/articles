int main() {
    
}


extern "C" int Fib(int n) {
    if ((n==1) || (n==2)) {
        return 1;
    }

    return Fib(n-1) + Fib(n-2);
}
