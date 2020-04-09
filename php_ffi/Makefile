build_cpp:
	echo 'Building cpp...'
	cd cpp && g++ -fPIC -O3 -shared src/php_cpp_ffi.cpp -o ../lib/libphp_cpp_ffi.so

build_go:
	echo 'Building golang...'
	cd golang && go build -o ../lib/libphp_go_ffi.so -buildmode=c-shared

build_rust:
	echo 'Building Rust...'
	cargo build --manifest-path rust/Cargo.toml --release && mv rust/target/release/libphp_ffi.so lib/libphp_rust_ffi.so

build: build_cpp build_go build_rust