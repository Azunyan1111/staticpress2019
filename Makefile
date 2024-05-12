.PHONY: zip

zip:
	rm ./staticpress2019.zip
	@echo "Creating ZIP file..."
	@cd ../ && zip -r ./staticpress2019/staticpress2019.zip staticpress2019/
	@echo "ZIP file created successfully."

up: zip
	docker stop some-wordpress
	docker rm some-wordpress
	docker stop some-mysql
	docker rm some-mysql
	docker run --name some-mysql -e MYSQL_ROOT_PASSWORD=my-secret-pw -e MYSQL_DATABASE=wordpress -d mysql
	sleep 6
	docker run --name some-wordpress --link some-mysql:mysql -p 1111:80 -e WORDPRESS_DB_HOST=mysql -e WORDPRESS_DB_USER=root -e WORDPRESS_DB_PASSWORD=my-secret-pw -e WORDPRESS_DB_NAME=wordpress -d wordpress
	docker cp ./wp-cli.phar some-wordpress:/usr/local/bin/wp
	docker exec some-wordpress /bin/bash -c "wp core install --url=localhost:1111 --title=Example --admin_user=admin --admin_password=passwordbdnjisadlans --admin_email=a@a.com --allow-root"
	docker exec some-wordpress /bin/bash -c "mkdir -p /var/www/html/wp-content/plugin"
	docker cp ../staticpress2019/staticpress2019.zip some-wordpress:/var/www/html/wp-content/plugin/
	docker exec some-wordpress /bin/bash -c "wp plugin install /var/www/html/wp-content/plugin/staticpress2019.zip --activate --allow-root"
	docker exec some-wordpress /bin/bash -c "wp staticpress2019 option http://localhost:1111/blog2/ /var/www/html/wp-content/uploads/staticpress2019/ --allow-root"
	docker exec some-wordpress /bin/bash -c "wp staticpress2019 build --allow-root"
	docker cp some-wordpress:/var/www/html/wp-content/uploads/staticpress2019/ ./tmp

