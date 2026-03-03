# Start with a super lightweight Nginx image
FROM nginx:alpine

# Upgrade packages to clear that Trivy vulnerability!
RUN apk update && apk upgrade --no-cache

# Copy our custom HTML file into the default Nginx public folder
COPY index.html /usr/share/nginx/html/index.html

# Expose port 80 inside the container
EXPOSE 80
