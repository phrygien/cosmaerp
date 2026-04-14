pipeline {
    agent any

    environment {
        APP_ENV = 'production'
        PROJECT_DIR = '/var/www/cosmaerp'
    }

    stages {

        stage('Preparation') {
            steps {
                sh '''
                    cd $PROJECT_DIR
                    git config --global --add safe.directory $PROJECT_DIR || true
                '''
            }
        }

        stage('Pull code') {
            steps {
                script {
                    // OLD COMMIT
                    env.OLD_COMMIT = sh(
                        script: "cd $PROJECT_DIR && git rev-parse HEAD",
                        returnStdout: true
                    ).trim()

                    // UPDATE CODE
                    sh '''
                        cd $PROJECT_DIR
                        git fetch origin
                        git reset --hard origin/main
                        git clean -fd
                    '''

                    // NEW COMMIT
                    env.NEW_COMMIT = sh(
                        script: "cd $PROJECT_DIR && git rev-parse HEAD",
                        returnStdout: true
                    ).trim()

                    echo "OLD: ${env.OLD_COMMIT}"
                    echo "NEW: ${env.NEW_COMMIT}"
                }
            }
        }

        stage('Check changes') {
            steps {
                script {

                    def composerChanged = sh(
                        script: """
                            cd $PROJECT_DIR
                            git diff --name-only ${env.OLD_COMMIT} ${env.NEW_COMMIT} | grep composer.lock || true
                        """,
                        returnStdout: true
                    ).trim()

                    env.COMPOSER_CHANGED = composerChanged ? "true" : "false"

                    def npmChanged = sh(
                        script: """
                            cd $PROJECT_DIR
                            git diff --name-only ${env.OLD_COMMIT} ${env.NEW_COMMIT} | grep package-lock.json || true
                        """,
                        returnStdout: true
                    ).trim()

                    env.NPM_CHANGED = npmChanged ? "true" : "false"

                    echo "Composer changed: ${env.COMPOSER_CHANGED}"
                    echo "NPM changed: ${env.NPM_CHANGED}"
                }
            }
        }

        stage('Composer install') {
            steps {
                script {
                    if (env.COMPOSER_CHANGED == "true") {
                        sh '''
                            cd $PROJECT_DIR
                            echo "📦 Composer install..."

                            composer update \
                                --no-dev \
                                --no-interaction \
                                --prefer-dist \
                                --optimize-autoloader \
                                --no-scripts
                        '''
                    } else {
                        echo "✅ Skip Composer install"
                    }
                }
            }
        }

        stage('NPM build') {
            steps {
                script {
                    if (env.NPM_CHANGED == "true") {
                        sh '''
                            cd $PROJECT_DIR
                            echo "📦 NPM build..."

                            npm ci
                            npm run build
                        '''
                    } else {
                        echo "✅ Skip NPM build"
                    }
                }
            }
        }

        stage('Laravel optimization') {
            steps {
                sh '''
                    cd $PROJECT_DIR

                    php artisan config:cache
                    php artisan route:cache
                    php artisan view:cache
                    php artisan cache:clear
                    php artisan package:discover || true
                '''
            }
        }

        stage('Fix permissions') {
            steps {
                sh '''
                    cd $PROJECT_DIR

                    sudo chgrp -R www-data storage bootstrap/cache
                    sudo chmod -R 775 storage bootstrap/cache
                '''
            }
        }
    }

    post {
        success {
            echo "✅ Déploiement réussi"
        }
        failure {
            echo "❌ Échec du déploiement"
        }
    }
}
