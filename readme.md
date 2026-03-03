🚀 TaskMaster API: 
Setup & CI/CD DocumentationThis repository contains a PHP/HTML-based task management application. We use Docker for local development and Jenkins for an automated "Smoke Test" deployment pipeline.1. 

Project Overview 
  Technology Stack: PHP (Apache), HTML, CSS.
Infrastructure: Docker & Docker Compose.CI/CD: Jenkins (running as a Docker container) with a multi-stage Jenkinsfile.

**2. Local Development Setup**
To run this project on your local machine (Mac/Ubuntu):
Clone the Repository:Bashgit clone https://github.com/ahsanalijee/taskmaster-api.git
cd taskmaster-api
Start the Application:Bashdocker-compose up -d --build
Access the App: Open http://localhost:8081 in your browser.

**3. Jenkins Infrastructure SetupWe run Jenkins inside Docker.**

To allow Jenkins to build our project images, it must "speak" to the host's Docker engine.A. Run Jenkins ContainerBashdocker run -d \
  -p 8085:8080 \
  --name jenkins-server \
  --restart=unless-stopped \
  -v jenkins_home:/var/jenkins_home \
  -v /var/run/docker.sock:/var/run/docker.sock \
  -u root \
  jenkins/jenkins:lts
  
B. Installing Docker & Compose inside JenkinsBecause the standard Jenkins image is "empty," we manually installed the necessary tools to avoid exit code 127 errors.Install Docker CLI:Bashdocker exec -u 0 -it jenkins-server bash

# Inside the container:
apt-get update && apt-get install -y curl
curl -fsSL https://get.docker.com -o get-docker.sh && sh get-docker.sh

Install Docker Compose:Bash# Inside the container:
apt-get install -y docker-compose-plugin
ln -s /usr/libexec/docker/cli-plugins/docker-compose /usr/bin/docker-compose
exit

Restart Jenkins: docker restart jenkins-server

4. The CI/CD Pipeline (Jenkinsfile)The pipeline is divided into stages to ensure the app is healthy before the final deployment.StageActionPurposeCheckout SCMPulls code from GitHubSyncs latest changes.Build Imagesdocker-compose buildCreates fresh PHP/Apache images.Clean Deploydocker-compose down && upEnsures no "orphan" containers are left.Smoke Testcurl http://localhost:8081Checks for a 200 OK status code.5. TroubleshootingBlank Page (8081): Ensure docker ps shows the taskmaster-api container as "Up."Permission Denied: Ensure the Jenkins container was started with -u root or the docker.sock has the correct permissions.Docker Not Found: Repeat the installation steps in Section 3B.
