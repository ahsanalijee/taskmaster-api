pipeline {
    agent any
    
    options {
        // Keeps only the last 5 build logs to save space
        buildDiscarder(logRotator(numToKeepStr: '5'))
    }

    environment {
        // Automatically uses the Jenkins build number as a version tag
        BUILD_TAG = "v${env.BUILD_NUMBER}"
        DOCKER_USER = "ahsanali250"
    }

    stages {
        stage('Checkout') {
            steps {
                // Pulls the latest code from your GitHub repository
                checkout scm
            }
        }
        
        stage('Build & Push API') {
            steps {
                script {
                    docker.withRegistry('', 'docker-hub-credentials') {
                        // Builds the API image from the root Dockerfile
                        def apiImage = docker.build("${DOCKER_USER}/taskmaster-api", "-f Dockerfile .")
                        
                        // Pushes both the versioned tag and 'latest' to the warehouse
                        apiImage.push("${env.BUILD_TAG}")
                        apiImage.push("latest")
                    }
                }
            }
        }

        stage('Build & Push Frontend') {
            steps {
                script {
                    docker.withRegistry('', 'docker-hub-credentials') {
                        // Builds the Frontend image from the subfolder
                        def webImage = docker.build("${DOCKER_USER}/taskmaster-web", "-f frontend/Dockerfile ./frontend")
                        
                        // Pushes both tags to Docker Hub
                        webImage.push("${env.BUILD_TAG}")
                        webImage.push("latest")
                    }
                }
            }
        }
    }

    // This section runs automatically after the stages finish
    post {
        always {
            script {
                // Cleans up local images to prevent your Mac from filling up
                sh "docker rmi ${DOCKER_USER}/taskmaster-api:${env.BUILD_TAG} || true"
                sh "docker rmi ${DOCKER_USER}/taskmaster-web:${env.BUILD_TAG} || true"
                echo "Cleanup complete: Local versioned images removed."
            }
        }
    }
}