pipeline {
    agent any

    environment {
        DOCKER_USER = "ahsanali250"
        BUILD_TAG = "v${env.BUILD_NUMBER}"
        // The Docker Bridge Gateway IP for Mac
        DOCKER_GATEWAY = "172.17.0.1"
    }

    stages {
        stage('Checkout') {
            steps {
                checkout scm
            }
        }

        stage('Unit Tests') {
            steps {
                script {
                    echo "Installing dependencies and running PHPUnit..."
                    sh "docker run --rm --volumes-from jenkins-server -w \"${WORKSPACE}\" composer install --ignore-platform-reqs"
                    sh "docker run --rm --volumes-from jenkins-server -w \"${WORKSPACE}\" php:8.2-cli vendor/bin/phpunit --configuration phpunit.xml || true"
                }
            }
            post {
                always {
                    junit testResults: 'build/report.xml', allowEmptyResults: true
                }
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
                    echo "Running Trivy Scan..."
                    sh "trivy image --format template --template '@/usr/local/share/trivy/templates/html.tpl' -o api-report.html ${DOCKER_USER}/taskmaster-api:latest || true"
                    sh "trivy image --format template --template '@/usr/local/share/trivy/templates/html.tpl' -o web-report.html ${DOCKER_USER}/taskmaster-web:latest || true"
                    sh "trivy image --severity HIGH,CRITICAL --exit-code 0 ${DOCKER_USER}/taskmaster-api:latest"
                    archiveArtifacts artifacts: '*.html', allowEmptyArchive: true
                }
            }
        }

        stage('Push to Docker Hub') {
            steps {
                script {
                    docker.withRegistry('', 'docker-hub-credentials') {
                        docker.image("${DOCKER_USER}/taskmaster-api:latest").push("latest")
                        docker.image("${DOCKER_USER}/taskmaster-web:latest").push("latest")
                    }
                }
            }
        }

        stage('Deploy & Smoke Test') {
            steps {
                script {
                    echo "Deploying stack..."
                    sh "docker-compose down"
                    sh "docker-compose up -d"
                    
                    echo "Waiting 15 seconds for stabilization..."
                    sleep 15
                    
                    // 1. Backend Test (via Gateway IP)
                    echo "Testing Backend API (Port 8080)..."
                    def apiStatus = sh(
                        script: "curl -L -s -o /dev/null -w '%{http_code}' http://${DOCKER_GATEWAY}:8080/health", 
                        returnStdout: true
                    ).trim()
                    
                    if (apiStatus != "200") {
                        error("API Failed! Expected 200 OK, but got ${apiStatus}")
                    } else {
                        echo "✅ API is healthy!"
                    }

                    // 2. Frontend Test (via Gateway IP)
                    echo "Testing Frontend Web Server (Port 8082)..."
                    def success = false
                    for (int i = 0; i < 5; i++) {
                        def webStatus = sh(
                            script: "curl -s -o /dev/null -w '%{http_code}' http://${DOCKER_GATEWAY}:8082 || true", 
                            returnStdout: true
                        ).trim()
                        
                        if (webStatus == "200") {
                            echo "✅ Frontend is healthy!"
                            success = true
                            break
                        }
                        echo "Attempt ${i+1}: Frontend status ${webStatus}. Retrying in 5s..."
                        sleep 5
                    }
                    
                    if (!success) {
                        sh "docker logs taskmaster-pipeline-web-1"
                        error("Frontend failed to respond after retries.")
                    }
                }
            }
        }
    }

    post {
        failure {
            sh "docker-compose logs --tail=50 || true"
        }
        always {
            sh "docker rmi ${DOCKER_USER}/taskmaster-api:${env.BUILD_TAG} || true"
            sh "docker rmi ${DOCKER_USER}/taskmaster-web:${env.BUILD_TAG} || true"
        }
    }
}