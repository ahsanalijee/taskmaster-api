pipeline {
    agent any

    environment {
        DOCKER_USER = "ahsanali250"
        DOCKER_GATEWAY = "172.17.0.1" 
    }

    stages {
        stage('Hard Reset & Pull') {
            steps {
                script {
                    echo "🧹 Clearing old workspace and pulling fresh code..."
                    deleteDir() // Deletes the current Jenkins workspace
                    checkout scm  // Pulls the latest code from GitHub
                }
            }
        }

        stage('Build Fresh Images') {
            steps {
                script {
                    echo "🏗️ Building images from scratch (no cache)..."
                    // '--no-cache' ensures we don't reuse old, broken layers
                    sh "docker build --no-cache -t ${DOCKER_USER}/taskmaster-api:latest -f Dockerfile ."
                    sh "docker build --no-cache -t ${DOCKER_USER}/taskmaster-web:latest -f frontend/Dockerfile ./frontend"
                }
            }
        }

        stage('Clean Deploy') {
            steps {
                script {
                    echo "🚀 Deploying fresh stack..."
                    sh "docker-compose down --remove-orphans" // Removes everything related to this project
                    sh "docker-compose up -d"
                    
                    echo "⏳ Waiting for services to initialize..."
                    sleep 20 // Giving it a little extra time for the first boot
                }
            }
        }

        stage('Final Smoke Test') {
            steps {
                script {
                    echo "🔍 Testing Backend via Gateway..."
                    def apiStatus = sh(script: "curl -L -s -o /dev/null -w '%{http_code}' http://${DOCKER_GATEWAY}:8080/health", returnStdout: true).trim()
                    
                    echo "🔍 Testing Frontend via Gateway..."
                    def webStatus = sh(script: "curl -s -o /dev/null -w '%{http_code}' http://${DOCKER_GATEWAY}:8082", returnStdout: true).trim()

                    if (apiStatus == "200" && webStatus == "200") {
                        echo "✅ EXERCISE SUCCESSFUL: Full Stack is Live!"
                    } else {
                        error "❌ Exercise Failed. API: ${apiStatus}, Web: ${webStatus}"
                    }
                }
            }
        }
    }
}