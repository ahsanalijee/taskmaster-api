pipeline {
    agent any

    environment {
        // Change this if your Docker Hub username is different
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
                    
                    // 1. We use --volumes-from to share the exact same files Jenkins sees.
                    // 2. We set the working directory (-w) to the current Jenkins Workspace path.
                    sh "docker run --rm --volumes-from jenkins-server -w \"${WORKSPACE}\" composer install --ignore-platform-reqs"
                    
                    // 3. We do the same for PHPUnit
                    sh "docker run --rm --volumes-from jenkins-server -w \"${WORKSPACE}\" php:8.2-cli vendor/bin/phpunit --configuration phpunit.xml || true"
                }
            }
            post {
                always {
                    // Generates the Test Result trend graph
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
                    sh "trivy image --severity HIGH,CRITICAL --exit-code 0 ${DOCKER_USER}/taskmaster-api:latest"
                    sh "trivy image --severity HIGH,CRITICAL --exit-code 0 ${DOCKER_USER}/taskmaster-web:latest"

                    // 3. Archive the HTML files
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

        stage('Deploy & Smoke Test') {
            steps {
                script {
                    echo "Deploying to local environment..."
                    sh "docker-compose down"
                    sh "docker-compose up -d"
                    
                    echo "Waiting for services to wake up..."
                    sleep 10
                    
                    echo "Performing Smoke Test (Vitals Check)..."
                    
                    // 1. We capture the HTTP status code into a variable
                    def httpStatus = sh(
                        script: "curl -s -o /dev/null -w '%{http_code}' http://localhost:8080", 
                        returnStdout: true
                    ).trim()
                    
                    echo "API returned HTTP Status: ${httpStatus}"
                    
                    // 2. We explicitly fail the build if it's not 200
                    if (httpStatus != "200") {
                        error("Smoke Test Failed! Expected 200 OK, but got ${httpStatus}")
                    } else {
                        echo "Smoke Test Passed! Application is healthy."
                    }
                }
            }
        }
    }

    post {
        failure {
            script {
                echo "Build Failed! Capturing container logs for debugging..."
                // Automatically prints app logs into Jenkins if the Smoke Test fails
                sh "docker-compose logs --tail=50 || true"
            }
        }
        always {
            script {
                echo "Cleanup: Removing versioned local images..."
                sh "docker rmi ${DOCKER_USER}/taskmaster-api:${env.BUILD_TAG} || true"
                sh "docker rmi ${DOCKER_USER}/taskmaster-web:${env.BUILD_TAG} || true"
            }
        }
    }
}