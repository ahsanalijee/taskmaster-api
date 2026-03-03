pipeline {
    agent any

    environment {
        // Change 'ahsanali250' to your actual Docker Hub username if different
        DOCKER_USER = "ahsanali250"
        BUILD_TAG = "v${env.BUILD_NUMBER}"
    }

    stages {
        stage('Checkout') {
            steps {
                // Pulls the latest code from your GitHub repository
                checkout scm
            }
        }

        stage('Build Images') {
            steps {
                script {
                    echo "Building Docker images for API and Frontend..."
                    // Build the API image
                    sh "docker build -t ${DOCKER_USER}/taskmaster-api:latest -f Dockerfile ."
                    // Build the Web/Frontend image
                    sh "docker build -t ${DOCKER_USER}/taskmaster-web:latest -f frontend/Dockerfile ./frontend"
                }
            }
        }

        stage('Security Scan') {
            steps {
                script {
                    echo "Running Trivy Vulnerability Scan..."
                    // Scan the images for High and Critical vulnerabilities
                    // We use --exit-code 0 so the build continues even if it finds issues (for now)
                    sh "trivy image --severity HIGH,CRITICAL --exit-code 0 ${DOCKER_USER}/taskmaster-api:latest"
                    sh "trivy image --severity HIGH,CRITICAL --exit-code 0 ${DOCKER_USER}/taskmaster-web:latest"
                }
            }
        }

        stage('Push to Docker Hub') {
            steps {
                script {
                    // Uses the credentials we stored in Jenkins (ID: docker-hub-credentials)
                    docker.withRegistry('', 'docker-hub-credentials') {
                        // Push API image with version tag and 'latest'
                        docker.image("${DOCKER_USER}/taskmaster-api:latest").push("${env.BUILD_TAG}")
                        docker.image("${DOCKER_USER}/taskmaster-api:latest").push("latest")

                        // Push Web image with version tag and 'latest'
                        docker.image("${DOCKER_USER}/taskmaster-web:latest").push("${env.BUILD_TAG}")
                        docker.image("${DOCKER_USER}/taskmaster-web:latest").push("latest")
                    }
                }
            }
        }

        stage('Deploy Locally') {
            steps {
                script {
                    echo "Restarting containers with new images..."
                    // 'down' removes old versions; 'up -d' starts new ones in background
                    sh "docker-compose down"
                    sh "docker-compose up -d"
                }
            }
        }
    }

    post {
        always {
            script {
                echo "Cleaning up local build images to save space..."
                // Removes the specific versioned images from your local machine after pushing
                sh "docker rmi ${DOCKER_USER}/taskmaster-api:${env.BUILD_TAG} || true"
                sh "docker rmi ${DOCKER_USER}/taskmaster-web:${env.BUILD_TAG} || true"
            }
        }
        success {
            echo "Successfully built, scanned, pushed, and deployed Build #${env.BUILD_NUMBER}!"
        }
        failure {
            echo "Build #${env.BUILD_NUMBER} failed. Check the Console Output for error logs."
        }
    }
}