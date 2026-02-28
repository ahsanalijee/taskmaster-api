# Start with a fresh Ubuntu 22.04 foundation
FROM ubuntu:22.04

# Prevent interactive prompts during apt-get installations
ENV DEBIAN_FRONTEND=noninteractive

# Update the OS and install PHP + MySQL extension
RUN apt-get update && apt-get install -y \
    php-cli \
    php-mysql \
    && rm -rf /var/lib/apt/lists/*

# Set our working directory inside the container
WORKDIR /var/www/html

# Copy our PHP app from your machine into the Ubuntu container
COPY index.php .

# Tell Docker we want to communicate on port 8080
EXPOSE 8080

# Spin up PHP's built-in lightweight web server
CMD ["php", "-S", "0.0.0.0:8080", "-t", "/var/www/html"]
