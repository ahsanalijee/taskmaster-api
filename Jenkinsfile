pipeline {
    agent any
    
    options {
        buildDiscarder(logRotator(numToKeepStr: '5'))
    }

    stages {
        stage('Checkout') {
            steps {
                checkout scm
            }
        }
        
        stage('Build API Image') {
            steps {
                script {
                    // Use your actual Docker Hub username here
                    docker.build("ahsanali250/taskmaster-api:latest", "-f Dockerfile .")
                }
            }
        }

        stage('Push to Hub') {
            steps {
                script {
                    // This uses the Credential ID we created in Step 1
                    docker.withRegistry('', 'docker-hub-credentials') {
                        docker.image("ahsanali250/taskmaster-api:latest").push()
                    }
                }
            }
        }
        
        stage('Security Scan') {
            steps {
                echo 'In a future step, we will add Trivy here to scan for vulnerabilities!'
            }
        }
    }
}