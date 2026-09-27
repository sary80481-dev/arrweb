FROM php:8.4-cli

RUN apt-get update && apt-get install -y \
    ffmpeg \
    python3 \
    python3-pip \
    nodejs \
    npm \
    ca-certificates \
    curl \
    unzip \
    git \
    && rm -rf /var/lib/apt/lists/*

# yt-dlp
RUN python3 -m pip install --break-system-packages --no-cache-dir -U yt-dlp

# bgutil yt-dlp PO Token Provider plugin
RUN python3 -m pip install --break-system-packages --no-cache-dir -U bgutil-ytdlp-pot-provider

# Download/build bgutil provider server
RUN git clone --depth 1 \
    https://github.com/Brainicism/bgutil-ytdlp-pot-provider.git \
    /opt/bgutil-ytdlp-pot-provider \
    && cd /opt/bgutil-ytdlp-pot-provider/server \
    && npm ci \
    && npx tsc

WORKDIR /app

COPY . /app

EXPOSE 8080

CMD ["sh", "-c", "node /opt/bgutil-ytdlp-pot-provider/server/build/main.js --host 127.0.0.1 --port 4416 & php -S 0.0.0.0:${PORT:-8080} server.php"]