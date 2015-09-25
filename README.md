# Educator

Official github repository of the Educator WordPress plugin.

WordPress.org link: https://wordpress.org/plugins/ibeducator/

## Unit Tests

The following files and directories are responsible for unit tests:

* tests/
* tests/bin/
* tests/tests/
* tests/.travis.yml
* tests/phpunit.xml

*Make sure that those files won't go to production.*

To run PHPUnit tests:

1. Go to wp-content/plugins/ibeducator
2. RUN: bash bin/install-wp-tests.sh wordpress_test root '' localhost latest
3. RUN: phpunit

The bin/install-wp-tests.sh script installs WordPress in /tmp/wordpress-tests-lib. The test installation uses supplied database credentials (root '' localhost).

## Pull Requests

When creating pull requests, please set the base(destination) branch to "development".
