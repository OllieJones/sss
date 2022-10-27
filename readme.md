# Super Sonic Search plugin

This uses [Valerian Saliou](https://github.com/valeriansaliou)'s fabulous high performance [Sonic](https://github.com/valeriansaliou/sonic) text search engine in your WordPress site.

Your audience gets flexible typo-tolerant search and autocompletion.

## Setting up Sonic

Sonic is an executable program in the Rust language.  It needs to be running for this plugin to work.

To build it from source, do this on the same OS version where the plugin will run. (TODO this isn't satisfactory for deployment, but let's get everything working first.)

```bash
curl --proto '=https' --tlsv1.2 -sSf https://sh.rustup.rs | sh
sudo apt install build-essential clang libclang-dev libc6-dev g++ llvm-dev
git clone git@github.com:valeriansaliou/sonic.git
cd sonic/
cargo build --release
cd target/release
```
