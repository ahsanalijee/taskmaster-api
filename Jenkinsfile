pipeline {
    agent any
    
    environment {
        BUILD_TAG = "v${env.BUILD_NUMBER}"
        DOCKER_USER = "ahsanali250"
    }

    stages {
        stage('Checkout') {
            steps {
                checkout scm
            }
        }
        
        stage('Build & Push Images') {
            steps {
                script {
                    docker.withRegistry('', 'docker-hub-credentials') {
                        // Build and Push API
                        def apiImage = docker.build("${DOCKER_USER}/taskmaster-api", "-f Dockerfile .")
                        apiImage.push("${env.BUILD_TAG}")
                        apiImage.push("latest")

                        // Build and Push Frontend
                        def webImage = docker.build("${DOCKER_USER}/taskmaster-web", "-f frontend/Dockerfile ./frontend")
                        webImage.push("${env.BUILD_TAG}")
                        webImage.push("latest")
                    }
                }
            }
        }

        stage('Deploy Locally') {
            steps {
                script {
                    // This command tells Docker Compose to recreate the containers 
                    // using the fresh images Jenkins just built.
                    sh "docker compose up -d"
                    echo "Deployment Complete! Containers are now running the latest code."
                }
            }
        }
    }

    post {
        always {
            script {
                sh "docker rmi ${DOCKER_USER}/taskmaster-api:${env.BUILD_TAG} || true"
                sh "docker rmi ${DOCKER_USER}/taskmaster-web:${env.BUILD_TAG} || true"
            }
        }
    }
}