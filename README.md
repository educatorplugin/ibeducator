# Educator

Official github repository of the Educator WordPress plugin.

WordPress.org link: https://wordpress.org/plugins/ibeducator/

## Unit Tests

The following files and directories are responsible for unit tests:
bin/
tests/
.travis.yml
phpunit.xml

*Make sure that those files won't go to production.*

To run PHPUnit tests:
1) Go to wp-content/plugins/ibeducator
2) RUN: bash bin/install-wp-tests.sh wordpress_test root '' localhost latest
3) RUN: phpunit

The bin/install-wp-tests.sh script installs WordPress in /tmp/wordpress-tests-lib. The test installation uses supplied database credentials (root '' localhost).