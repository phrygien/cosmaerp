pipeline {
    agent any

    environment {
        APP_ENV = 'production'
        PROJECT_DIR = '/var/www/cosmaerp'
        PHP_BIN = 'php'
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
                sh '''
                    cd $PROJECT_DIR

                    git fetch origin

                    # Sauvegarder ancien commit
                    OLD_COMMIT=$(git rev-parse HEAD)

                    git reset --hard origin/main
                    git clean -fd

                    # Nouveau commit
                    NEW_COMMIT=$(git rev-parse HEAD)

                    echo "OLD_COMMIT=$OLD_COMMIT" > .commit_env
                    echo "NEW_COMMIT=$NEW_COMMIT" >> .commit_env
                '''
            }
        }

        stage('Check Composer changes') {
            steps {
                sh '''
                    cd $PROJECT_DIR
                    source .commit_env

                    if git diff --name-only $OLD_COMMIT $NEW_COMMIT | grep -q "composer.lock"; then
                        echo "COMPOSER_CHANGED=true" > .build_flags
                    else
                        echo "COMPOSER_CHANGED=false" > .build_flags
                    fi
                '''
            }
        }

        stage('Install dependencies (Composer)') {
            steps {
                sh '''
                    cd $PROJECT_DIR
                    source .build_flags

                    if [ "$COMPOSER_CHANGED" = "true" ]; then
                        echo "composer.lock modifié → installation dépendances"

                        composer install \
                            --no-dev \
                            --no-interaction \
                            --prefer-dist \
                            --optimize-autoloader
                    else
                        echo "Aucun changement composer → skip"
                    fi
                '''
            }
        }

        stage('Build assets') {
            steps {
                sh '''
                    cd $PROJECT_DIR
                    npm ci
                    npm run build
                '''
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
            echo "Déploiement optimisé réussi"
        }
        failure {
            echo "Échec du déploiement"
        }
    }
}
