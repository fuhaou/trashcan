#!/bin/bash

function start_service() {
  check_hosts=$(grep local-onex-passport.epsilo.io /etc/hosts)

  if [[ ! $check_hosts ]]; then
    echo "Add vhost local-onex-passport.epsilo.io"
    echo "127.0.0.1 local-onex-passport.epsilo.io" | sudo tee -a /etc/hosts > /dev/null
  fi

#  cp -f ../.env.dev ../.env #map config in docker-compose
  chmod -R 777 ../storage
  docker-compose up -d
  docker_id=$(docker ps | grep php-fpm-onex-passport | awk '{print $1}')
  echo "Run migration and composer in container: $docker_id"
  docker exec -it "$docker_id" sh -c "composer config --global bitbucket-oauth.bitbucket.org u9a2UWad9kvKABMpgZ SCjhQgxacv8q48FSjaeA6U2gMqwaHRJk"
  docker exec -it "$docker_id" sh -c "composer install -d /source_code/ && chown -R 1000:1000 /source_code/vendor"
  docker exec -it "$docker_id" sh -c "php /source_code/artisan migrate"
  docker exec -it "$docker_id" sh -c "php /source_code/artisan cache:clear"
  docker exec -it "$docker_id" sh -c "chmod -R 777 /source_code/storage"
  echo "Done deploy local-onex-passport.epsilo.io"
  echo "URL Passport: http://local-onex-passport.epsilo.io"
  echo "To see the logs, please run docker-compose logs -f"
}
function stop_service() {
  docker-compose down
  echo "Service stopped"
}
function run_artisan() {
  docker_id=$(docker ps | grep php-fpm-onex-passport | awk '{print $1}')
  if [ "$docker_id" ]; then
    echo -ne "php artisan "
    read -r parameter
    docker exec -ti "$docker_id" sh -c "php /source_code/artisan $parameter"
  else
    echo "=================================================="
    echo "Service not start yet, please start service first!"
    echo "=================================================="
  fi
}
function run_composer_install() {
  docker_id=$(docker ps | grep php-fpm-onex-passport | awk '{print $1}')
  if [ "$docker_id" ]; then
    docker exec -ti "$docker_id" sh -c "cd /source_code/ && rm -rf composer.lock \
		&& composer install && chown -R 1000:1000 vendor"
  else
    echo "=================================================="
    echo "Service not start yet, please start service first!"
    echo "=================================================="
  fi
}
function run_composer() {
  docker_id=$(docker ps | grep php-fpm-onex-passport | awk '{print $1}')
  if [ "$docker_id" ]; then
    echo -ne "composer "
    read -r command
    docker exec -ti "$docker_id" sh -c "cd /source_code/ && composer $command"
	docker exec -ti "$docker_id" sh -c "chown -R 1000:1000 /source_code/vendor"
  else
    echo "=================================================="
    echo "Service not start yet, please start service first!"
    echo "=================================================="
  fi
}
##
# Color  Variables
##
green='\e[92m'
red='\e[91m'
yellow='\e[93m'
clear='\e[0m'
##
# Color Functions
##
ColorGreen() {
  echo -ne "$green$1$clear"
}
ColorRed() {
  echo -ne "$red$1$clear"
}
ColorYellow() {
  echo -ne "$yellow$1$clear"
}
menu() {
  echo -ne "
	$(ColorGreen '1) Start service')
	$(ColorGreen '2) Run artisan')
	$(ColorGreen '3) Run composer')
	$(ColorGreen '4) Run composer install')
	$(ColorRed '5) Stop service')
	$(ColorYellow '0) Exit')
	$(ColorGreen 'Choose an option: ')"
  read -r a
  case $a in
  1)
    start_service
    menu
    ;;
  2)
    run_artisan
    menu
    ;;
  3)
    run_composer
    menu
    ;;
  4)
    run_composer_install
    menu
    ;;
  5)
    stop_service
    menu
    ;;
  0) exit 0 ;;
  *)
    echo -e "$red Wrong option.$clear"
    menu
    ;;
  esac
}
# Call the menu function
menu
