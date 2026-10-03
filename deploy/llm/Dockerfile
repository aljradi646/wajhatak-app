FROM ghcr.io/ggml-org/llama.cpp:server

ENV LLAMA_CACHE=/models \
    LLAMA_ARG_HF_REPO=tensorblock/SmolLM2-135M-Instruct-GGUF:Q8_0 \
    LLAMA_ARG_HF_FILE=SmolLM2-135M-Instruct-Q8_0.gguf \
    LLAMA_ARG_MODEL="" \
    LLAMA_ARG_HOST=0.0.0.0 \
    LLAMA_ARG_PORT=8080 \
    LLAMA_ARG_ALIAS=wajhatak-smollm2-135m-instruct-q8_0 \
    LLAMA_ARG_CTX_SIZE=4096 \
    LLAMA_ARG_N_PREDICT=256 \
    LLAMA_ARG_THREADS=2 \
    LLAMA_ARG_PARALLEL=1

EXPOSE 8080

VOLUME ["/models"]
