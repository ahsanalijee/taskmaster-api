pipeline {
    agent any

    environment {
        DOCKER_USER = "ahsanali250"
        BUILD_TAG = "v${env.BUILD_NUMBER}"
    }

    stages {
        stage('Checkout') {
            steps {
                checkout scm
            }
        }

        stage('Build Images') {
            steps {
                script {
                    echo "Building Docker images..."
                    sh "docker build -t ${DOCKER_USER}/taskmaster-api:latest -f Dockerfile ."
                    sh "docker build -t ${DOCKER_USER}/taskmaster-web:latest -f frontend/Dockerfile ./frontend"
                }
            }
        }

        stage('Security Scan') {
            steps {
                script {
                    echo "Running Trivy Scan & Generating HTML Reports..."
                    
                    // 1. Scan API and generate HTML report
                    // Note: If 'html.tpl' is in a different path, update the '@' path below
                    sh "trivy image --format template --template '@/usr/local/share/trivy/templates/html.tpl' -o api-report.html ${DOCKER_USER}/taskmaster-api:latest"
                    
                    // 2. Scan Web and generate HTML report
                    sh "trivy image --format template --template '@/usr/local/share/trivy/templates/html.tpl' -o web-report.html ${DOCKER_USER}/taskmaster-web:latest"
                    
                    // 3. Keep the console table for quick viewing in Jenkins logs
                    sh "trivy image --severity HIGH,CRITICAL --exit-code 0 ${DOCKER_USER}/taskmaster-api:latest"
                    sh "trivy image --severity HIGH,CRITICAL --exit-code 0 ${DOCKER_USER}/taskmaster-web:latest"

                    // Always archive so we can see the results even if the build fails later
                    archiveArtifacts artifacts: '*.html', allowEmptyArchive: true
                }
            }
        }

       

        stage('Push to Docker Hub') {
            steps {
                script {
                    docker.withRegistry('', 'docker-hub-credentials') {
                        docker.image("${DOCKER_USER}/taskmaster-api:latest").push("${env.BUILD_TAG}")
                        docker.image("${DOCKER_USER}/taskmaster-api:latest").push("latest")

                        docker.image("${DOCKER_USER}/taskmaster-web:latest").push("${env.BUILD_TAG}")
                        docker.image("${DOCKER_USER}/taskmaster-web:latest").push("latest")
                    }
                }
            }
        }

        stage('Deploy Locally') {
            steps {
                script {
                    sh "docker-compose down"
                    sh "docker-compose up -d"
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