pipeline {
    agent any

    environment {
        // Your Docker Hub username
        DOCKER_USER = "ahsanali250"
        BUILD_TAG = "v${env.BUILD_NUMBER}"
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
                    
                    // 1. Install Composer dependencies using the shared Jenkins volume
                    sh "docker run --rm --volumes-from jenkins-server -w \"${WORKSPACE}\" composer install --ignore-platform-reqs"
                    
                    // 2. Run PHPUnit tests. The '|| true' ensures the build continues even if tests fail in Learning Mode.
                    // PRODUCTION: Remove '|| true' to strictly fail the build if logic is broken.
                    sh "docker run --rm --volumes-from jenkins-server -w \"${WORKSPACE}\" php:8.2-cli vendor/bin/phpunit --configuration phpunit.xml || true"
                }
            }
            post {
                always {
                    // Generates the Test Result trend graph on your Jenkins dashboard
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
                    echo "Running Trivy Scan & Generating HTML Reports..."
                    
                    // 1. Generate HTML reports for Jenkins UI
                    sh "trivy image --format template --template '@/usr/local/share/trivy/templates/html.tpl' -o api-report.html ${DOCKER_USER}/taskmaster-api:latest || true"
                    sh "trivy image --format template --template '@/usr/local/share/trivy/templates/html.tpl' -o web-report.html ${DOCKER_USER}/taskmaster-web:latest || true"
                    
                    // 2. Console output for quick viewing 
                    // PRODUCTION: Change '--exit-code 0' to '--exit-code 1' to block vulnerable images
                    sh "trivy image --severity HIGH,CRITICAL --exit-code 0 ${DOCKER_USER}/taskmaster-api:latest"
                    sh "trivy image --severity HIGH,CRITICAL --exit-code 0 ${DOCKER_USER}/taskmaster-web:latest"

                    // 3. Archive the HTML files so you can download them from Jenkins
                    archiveArtifacts artifacts: '*.html', allowEmptyArchive: true
                }
            }
        }

        stage('Push to Docker Hub') {
            steps {
                script {
                    docker.withRegistry('', 'docker-hub-credentials') {
                        // Push API Image
                        docker.image("${DOCKER_USER}/taskmaster-api:latest").push("${env.BUILD_TAG}")
                        docker.image("${DOCKER_USER}/taskmaster-api:latest").push("latest")

                        // Push Web Image
                        docker.image("${DOCKER_USER}/taskmaster-web:latest").push("${env.BUILD_TAG}")
                        docker.image("${DOCKER_USER}/taskmaster-web:latest").push("latest")
                    }
                }
            }
        }

        stage('Deploy & Smoke Test') {
            steps {
                script {
                    echo "Deploying to local environment..."
                    sh "docker-compose down"
                    sh "docker-compose up -d"
                    
                    echo "Waiting for services and database to wake up..."
                    sleep 10
                    
                    echo "========================================"
                    echo "Performing Full Stack Smoke Test..."
                    echo "========================================"
                    
                    // 1. Test the Backend API (Port 8080)
                    echo "Testing Backend API (/health)..."
                    def apiStatus = sh(
                        script: "curl -L -s -o /dev/null -w '%{http_code}' http://localhost:8080/health", 
                        returnStdout: true
                    ).trim()
                    
                    if (apiStatus != "200") {
                        error("API Failed! Expected 200 OK, but got ${apiStatus}")
                    } else {
                        echo "✅ API is healthy! (200 OK)"
                    }

                    // 2. Test the Frontend Web Server (Port 80)
                    echo "Testing Frontend Web Server..."
                    def webStatus = sh(
                        script: "curl -s -o /dev/null -w '%{http_code}' http://localhost", 
                        returnStdout: true
                    ).trim()
                    
                    if (webStatus != "200") {
                        error("Frontend Failed! Expected 200 OK, but got ${webStatus}")
                    } else {
                        echo "✅ Frontend is healthy! (200 OK)"
                    }
                    
                    echo "🚀 Full Stack Smoke Test Passed!"
                }
            }
        }
    }

    post {
        failure {
            script {
                echo "🚨 Build Failed! Capturing container logs for debugging..."
                // Automatically prints app logs into Jenkins if the Smoke Test fails
                sh "docker-compose logs --tail=50 || true"
            }
        }
        always {
            script {
                echo "🧹 Cleanup: Removing versioned local images to save space..."
                sh "docker rmi ${DOCKER_USER}/taskmaster-api:${env.BUILD_TAG} || true"
                sh "docker rmi ${DOCKER_USER}/taskmaster-web:${env.BUILD_TAG} || true"
                // Optional: shut down the test environment after passing
                // sh "docker-compose down" 
            }
        }
    }
}