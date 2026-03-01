pipeline {
    agent any
    
    // We define a variable for the build tag to keep the code clean
    environment {
        BUILD_TAG = "v${env.BUILD_NUMBER}"
    }

    stages {
        stage('Checkout') {
            steps {
                checkout scm
            }
        }
        
        stage('Build & Push API') {
            steps {
                script {
                    docker.withRegistry('', 'docker-hub-credentials') {
                        // We build the image once
                        def apiImage = docker.build("ahsanali250/taskmaster-api", "-f Dockerfile .")
                        
                        // We push two tags: the specific build version and 'latest'
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
                        def webImage = docker.build("ahsanali250/taskmaster-web", "-f frontend/Dockerfile ./frontend")
                        
                        webImage.push("${env.BUILD_TAG}")
                        webImage.push("latest")
                    }
                }
            }
        }
    }
}