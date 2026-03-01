pipeline {
    agent any
    
    // This allows Jenkins to use the Docker socket we shared with it
    options {
        buildDiscarder(logRotator(numToKeepStr: '5'))
    }

    stages {
        stage('Checkout') {
            steps {
                // Pulls your latest code from the GitHub branch
                checkout scm
            }
        }
        
        stage('Build API Image') {
            steps {
                script {
                    // Builds your image just like you did manually
                    docker.build("ahsanali250/taskmaster-api:latest", "-f Dockerfile .")
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