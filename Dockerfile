FROM php:8.4-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    ffmpeg \
    python3 \
    python3-pip \
    ca-certificates \
    curl \
    unzip \
    git \
    && rm -rf /var/lib/apt/lists/*

# Install latest yt-dlp
RUN pip3 install --break-system-packages --no-cache-dir -U yt-dlp

# Application directory
WORKDIR /app

# Copy project
COPY . /app

# Railway uses PORT dynamically
EXPOSE 8080

# Start PHP built-in server
CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8080} server.php"]